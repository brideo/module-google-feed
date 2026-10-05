<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Management;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use UpturnStudio\GoogleFeed\Model\Feed\FeedFiltersBuilder;
use UpturnStudio\GoogleFeed\Model\Feed\Generator;
use UpturnStudio\GoogleFeed\Model\Feed\ItemBuilder;
use UpturnStudio\GoogleFeed\Model\Feed\ListingRules;
use UpturnStudio\GoogleFeed\Model\Feed\ProductBatchLoader;
use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;
use UpturnStudio\GoogleFeed\Model\Feed\Stock\SalabilityProvider;
use UpturnStudio\GoogleFeed\Model\Feed\ValueResolver;
use UpturnStudio\GoogleFeed\Model\FeedRepository;
use UpturnStudio\GoogleFeed\Model\ResourceModel\CatalogData;
use UpturnStudio\GoogleFeed\Model\ResourceModel\ProductAttribute as ProductAttributeResource;

/**
 * Shows what one SKU would look like in a feed, optionally under a mapping that has not been saved, so a change can
 * be checked before it is made.
 */
class ItemPreviewService
{
    /**
     * @param FeedRepository $feedRepository
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     * @param ProductResource $productResource
     * @param ProductBatchLoader $batchLoader
     * @param CatalogData $catalogData
     * @param SalabilityProvider $salabilityProvider
     * @param ProductAttributeResource $productAttributeResource
     * @param ValueResolver $valueResolver
     * @param ItemBuilder $itemBuilder
     * @param FeedFiltersBuilder $filtersBuilder
     * @param ListingRules $listingRules
     * @param FeedInputNormalizer $normalizer
     * @param FeedValidator $validator
     * @param State $appState
     */
    public function __construct(
        private readonly FeedRepository $feedRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly Emulation $emulation,
        private readonly ProductResource $productResource,
        private readonly ProductBatchLoader $batchLoader,
        private readonly CatalogData $catalogData,
        private readonly SalabilityProvider $salabilityProvider,
        private readonly ProductAttributeResource $productAttributeResource,
        private readonly ValueResolver $valueResolver,
        private readonly ItemBuilder $itemBuilder,
        private readonly FeedFiltersBuilder $filtersBuilder,
        private readonly ListingRules $listingRules,
        private readonly FeedInputNormalizer $normalizer,
        private readonly FeedValidator $validator,
        private readonly State $appState
    ) {
    }

    /**
     * Preview a SKU.
     *
     * @param int $feedId
     * @param string $sku
     * @param array[]|null $mappingRows Mapping to try instead of the feed's own
     * @return array With keys feed_id, sku, listed, reason and attributes
     * @throws InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function preview(int $feedId, string $sku, ?array $mappingRows = null): array
    {
        $feed = $this->feedRepository->getById($feedId);
        $mapping = $feed->getMapping();
        if ($mappingRows !== null) {
            $mapping = $this->normalizer->normalizeMappingRows($mappingRows);
            $errors = $this->validator->validateMapping($mapping);
            if ($errors) {
                $exception = new InputException(new Phrase('The mapping is not valid.'));
                foreach ($errors as $error) {
                    $exception->addError(new Phrase('%1', [$error]));
                }
                throw $exception;
            }
        }

        $storeId = $feed->getStoreId();

        // The stdio MCP server runs with no area at all, and store emulation needs one.
        return $this->appState->emulateAreaCode(Area::AREA_FRONTEND, function () use ($storeId, $feedId, $feed, $sku, $mapping) {
            $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            try {
                return $this->build($feedId, $feed, trim($sku), $mapping);
            } finally {
                $this->emulation->stopEnvironmentEmulation();
            }
        });
    }

    /**
     * @param int $feedId
     * @param \UpturnStudio\GoogleFeed\Model\Feed $feed
     * @param string $sku
     * @param array[] $mapping
     * @return array
     */
    private function build(int $feedId, \UpturnStudio\GoogleFeed\Model\Feed $feed, string $sku, array $mapping): array
    {
        $storeId = $feed->getStoreId();
        $store = $this->storeManager->getStore($storeId);
        $productId = (int) $this->productResource->getIdBySku($sku);
        if (!$productId) {
            return $this->result($feedId, $sku, 'No product has this SKU.');
        }

        $attributeCodes = $this->valueResolver->getAttributeCodes($mapping);
        $withGallery = $this->valueResolver->usesResolver($mapping, 'additional_images');
        $product = $this->batchLoader->loadByIds($storeId, [$productId], $attributeCodes, $withGallery)[$productId] ?? null;
        if ($product === null) {
            return $this->result($feedId, $sku, 'It is disabled or not assigned to the feed\'s website.');
        }
        $sku = (string) $product->getSku();

        $filters = $this->filtersBuilder->build($feed);
        if ($product->getTypeId() === 'configurable') {
            return $this->result(
                $feedId,
                $sku,
                'Configurable products are not listed themselves; preview one of its variants instead.'
            );
        }
        if (!in_array($product->getTypeId(), $filters->typeIds, true)) {
            return $this->result($feedId, $sku, 'Its product type is not one the feed includes.');
        }
        if ($filters->attributeSetIds && !in_array((int) $product->getAttributeSetId(), $filters->attributeSetIds, true)) {
            return $this->result($feedId, $sku, 'Its attribute set is not one the feed includes.');
        }

        $parent = $this->findParent($productId, $storeId, $attributeCodes, $withGallery);
        $categoryIds = $this->catalogData->getCategoryIdsByProductIds(array_filter([$productId, $parent?->getId()]));
        $salable = in_array($product->getTypeId(), Generator::COMPOSITE_TYPES, true)
            ? (bool) $product->isSalable()
            : ($this->salabilityProvider->getSalableMap([$sku], (int) $store->getWebsiteId())[mb_strtolower($sku)] ?? false);

        $context = new ProductContext(
            $product,
            $parent,
            $store,
            $feed,
            $salable,
            array_merge(
                $categoryIds[$productId] ?? [],
                $parent ? ($categoryIds[(int) $parent->getId()] ?? []) : []
            )
        );
        $overrides = $this->productAttributeResource->getEffectiveValues([$sku], $storeId)[mb_strtolower($sku)] ?? [];
        $item = $this->itemBuilder->build($context, $mapping, $overrides);
        $reason = $this->listingRules->getExclusionReason($context, $filters);
        if ($reason === null && $item === null) {
            $reason = 'It has no ID or no link, which Google requires.';
        }

        return $this->result($feedId, $sku, $reason, $item ?? []);
    }

    /**
     * The first enabled configurable parent of a product, null when it has none.
     *
     * @param int $productId
     * @param int $storeId
     * @param string[] $attributeCodes
     * @param bool $withGallery
     * @return Product|null
     */
    private function findParent(int $productId, int $storeId, array $attributeCodes, bool $withGallery): ?Product
    {
        $parentIds = $this->catalogData->getParentIdsByChildIds([$productId])[$productId] ?? [];
        $parents = $this->batchLoader->loadByIds($storeId, $parentIds, $attributeCodes, $withGallery);
        foreach ($parentIds as $parentId) {
            if (isset($parents[$parentId])) {
                return $parents[$parentId];
            }
        }

        return null;
    }

    /**
     * @param int $feedId
     * @param string $sku
     * @param string|null $reason
     * @param string[][] $item
     * @return array
     */
    private function result(int $feedId, string $sku, ?string $reason, array $item = []): array
    {
        $attributes = [];
        foreach ($item as $code => $values) {
            $attributes[] = ['code' => (string) $code, 'values' => array_values($values)];
        }

        return [
            'feed_id' => $feedId,
            'sku' => $sku,
            'listed' => $reason === null,
            'reason' => $reason,
            'attributes' => $attributes,
        ];
    }
}
