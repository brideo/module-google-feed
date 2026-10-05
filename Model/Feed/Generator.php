<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use UpturnStudio\GoogleFeed\Model\Config;
use UpturnStudio\GoogleFeed\Model\Feed;
use UpturnStudio\GoogleFeed\Model\Feed\Stock\SalabilityProvider;
use UpturnStudio\GoogleFeed\Model\Feed\Xml\ItemWriter;
use UpturnStudio\GoogleFeed\Model\ResourceModel\CatalogData;
use UpturnStudio\GoogleFeed\Model\ResourceModel\ProductAttribute as ProductAttributeResource;
use XMLWriter;

/**
 * Writes a feed's XML file.
 *
 * Products are read in chunks and streamed to a temporary file, which replaces the public file only once the
 * whole feed has been written, so Merchant Center never fetches a partial feed.
 */
class Generator
{
    public const DEFAULT_TYPES = ['simple', 'virtual', 'downloadable'];

    /**
     * Types whose stock is derived from their children rather than their own source items.
     */
    public const COMPOSITE_TYPES = ['bundle', 'grouped'];

    private const PARENT_CACHE_LIMIT = 1000;

    /**
     * @var Product[] Parent entity ID => product, or false when it is disabled or not in the store
     */
    private array $parents = [];

    /**
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     * @param Filesystem $filesystem
     * @param Config $config
     * @param ProductBatchLoader $batchLoader
     * @param CatalogData $catalogData
     * @param ProductAttributeResource $productAttributeResource
     * @param SalabilityProvider $salabilityProvider
     * @param ValueResolver $valueResolver
     * @param ItemBuilder $itemBuilder
     * @param ItemWriter $itemWriter
     * @param FeedFiltersBuilder $filtersBuilder
     * @param ListingRules $listingRules
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly Emulation $emulation,
        private readonly Filesystem $filesystem,
        private readonly Config $config,
        private readonly ProductBatchLoader $batchLoader,
        private readonly CatalogData $catalogData,
        private readonly ProductAttributeResource $productAttributeResource,
        private readonly SalabilityProvider $salabilityProvider,
        private readonly ValueResolver $valueResolver,
        private readonly ItemBuilder $itemBuilder,
        private readonly ItemWriter $itemWriter,
        private readonly FeedFiltersBuilder $filtersBuilder,
        private readonly ListingRules $listingRules
    ) {
    }

    /**
     * Generate the feed file.
     *
     * @param Feed $feed
     * @return int Number of items written
     */
    public function generate(Feed $feed): int
    {
        $store = $this->storeManager->getStore($feed->getStoreId());
        $this->parents = [];

        $this->emulation->startEnvironmentEmulation((int) $store->getId(), Area::AREA_FRONTEND, true);
        try {
            return $this->write($feed, $store);
        } finally {
            $this->emulation->stopEnvironmentEmulation();
            $this->parents = [];
        }
    }

    /**
     * Stream every matching product into the feed file.
     *
     * @param Feed $feed
     * @param StoreInterface $store
     * @return int
     */
    private function write(Feed $feed, StoreInterface $store): int
    {
        $storeId = (int) $store->getId();
        $mapping = $feed->getMapping();
        $filters = $this->filtersBuilder->build($feed);
        $attributeCodes = $this->valueResolver->getAttributeCodes($mapping);
        $withGallery = $this->valueResolver->usesResolver($mapping, 'additional_images');
        $batchSize = $this->config->getBatchSize();

        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $finalPath = $feed->getRelativePath();
        $temporaryPath = $finalPath . '.tmp';
        $mediaDirectory->create(Feed::DIRECTORY);
        $stream = $mediaDirectory->openFile($temporaryPath, 'w');

        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $this->itemWriter->startFeed(
            $xml,
            $feed->getName(),
            (string) $store->getBaseUrl(UrlInterface::URL_TYPE_LINK),
            (string) $store->getName()
        );

        $count = 0;
        $lastId = 0;
        try {
            do {
                $products = $this->batchLoader->loadAfter(
                    $storeId,
                    $lastId,
                    $batchSize,
                    $filters->typeIds,
                    $filters->attributeSetIds,
                    $attributeCodes,
                    $withGallery
                );
                if (!$products) {
                    break;
                }
                $lastId = (int) end($products)->getId();

                $productIds = array_map(static fn (Product $product): int => (int) $product->getId(), $products);
                $parentIds = $this->catalogData->getParentIdsByChildIds($productIds);
                $this->loadParents($storeId, $parentIds, $attributeCodes, $withGallery);
                $allIds = array_merge($productIds, ...array_values($parentIds));
                $categories = $this->catalogData->getCategoryIdsByProductIds(array_values(array_unique($allIds)));
                $skus = array_map(static fn (Product $product): string => (string) $product->getSku(), $products);
                $salable = $this->salabilityProvider->getSalableMap($skus, (int) $store->getWebsiteId());
                $overrides = $this->productAttributeResource->getEffectiveValues($skus, $storeId);

                foreach ($products as $product) {
                    $skuKey = mb_strtolower((string) $product->getSku());
                    $parent = $this->pickParent($parentIds[(int) $product->getId()] ?? []);
                    $itemCategories = array_merge(
                        $categories[(int) $product->getId()] ?? [],
                        $parent ? ($categories[(int) $parent->getId()] ?? []) : []
                    );
                    $isSalable = in_array($product->getTypeId(), self::COMPOSITE_TYPES, true)
                        ? (bool) $product->isSalable()
                        : ($salable[$skuKey] ?? false);

                    $context = new ProductContext($product, $parent, $store, $feed, $isSalable, $itemCategories);
                    if ($this->listingRules->getExclusionReason($context, $filters) !== null) {
                        continue;
                    }
                    $item = $this->itemBuilder->build($context, $mapping, $overrides[$skuKey] ?? []);
                    if ($item === null) {
                        continue;
                    }
                    $this->itemWriter->writeItem($xml, $item);
                    $count++;
                }

                $stream->write($xml->flush(true));
                if (count($this->parents) > self::PARENT_CACHE_LIMIT) {
                    $this->parents = [];
                }
            } while (count($products) === $batchSize);

            $this->itemWriter->endFeed($xml);
            $stream->write($xml->flush(true));
        } catch (\Throwable $e) {
            $stream->close();
            $mediaDirectory->delete($temporaryPath);
            throw $e;
        }

        $stream->close();
        $mediaDirectory->renameFile($temporaryPath, $finalPath);

        return $count;
    }

    /**
     * Load parents that are not cached yet.
     *
     * @param int $storeId
     * @param int[][] $parentIds
     * @param string[] $attributeCodes
     * @param bool $withGallery
     * @return void
     */
    private function loadParents(int $storeId, array $parentIds, array $attributeCodes, bool $withGallery): void
    {
        $missing = [];
        foreach ($parentIds as $ids) {
            foreach ($ids as $id) {
                if (!array_key_exists($id, $this->parents)) {
                    $missing[$id] = $id;
                }
            }
        }
        if (!$missing) {
            return;
        }
        $loaded = $this->batchLoader->loadByIds($storeId, array_values($missing), $attributeCodes, $withGallery);
        foreach ($missing as $id) {
            $this->parents[$id] = $loaded[$id] ?? false;
        }
    }

    /**
     * First enabled parent, null when the product is not a variant.
     *
     * @param int[] $parentIds
     * @return Product|null
     */
    private function pickParent(array $parentIds): ?Product
    {
        foreach ($parentIds as $parentId) {
            if (!empty($this->parents[$parentId])) {
                return $this->parents[$parentId];
            }
        }

        return null;
    }
}
