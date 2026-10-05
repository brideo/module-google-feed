<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Model\Config as EavConfig;

/**
 * Loads enabled products for a store in chunks, with only the attributes a feed reads.
 */
class ProductBatchLoader
{
    /**
     * Attributes needed for pricing, URLs, images and visibility whatever the mapping says.
     */
    private const BASE_ATTRIBUTES = [
        'name',
        'image',
        'price',
        'special_price',
        'special_from_date',
        'special_to_date',
        'tax_class_id',
        'visibility',
        'status',
        'weight',
        'url_key',
        'price_type',
        'price_view',
    ];

    /**
     * @var bool[] Attribute code => exists on products
     */
    private array $knownAttributes = [];

    /**
     * @param CollectionFactory $collectionFactory
     * @param EavConfig $eavConfig
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly EavConfig $eavConfig
    ) {
    }

    /**
     * Next chunk of products of the given types, in entity ID order.
     *
     * @param int $storeId
     * @param int $afterId
     * @param int $limit
     * @param string[] $typeIds
     * @param int[] $attributeSetIds Empty for any
     * @param string[] $attributeCodes
     * @param bool $withGallery
     * @return Product[]
     */
    public function loadAfter(
        int $storeId,
        int $afterId,
        int $limit,
        array $typeIds,
        array $attributeSetIds,
        array $attributeCodes,
        bool $withGallery
    ): array {
        $collection = $this->createCollection($storeId, $attributeCodes);
        $collection->addFieldToFilter('entity_id', ['gt' => $afterId])
            ->addFieldToFilter('type_id', ['in' => $typeIds])
            ->setOrder('entity_id', Collection::SORT_ORDER_ASC)
            ->setPageSize($limit)
            ->setCurPage(1);
        if ($attributeSetIds) {
            $collection->addFieldToFilter('attribute_set_id', ['in' => $attributeSetIds]);
        }

        return $this->fetch($collection, $withGallery);
    }

    /**
     * Enabled products by ID, keyed by entity ID.
     *
     * @param int $storeId
     * @param int[] $productIds
     * @param string[] $attributeCodes
     * @param bool $withGallery
     * @return Product[]
     */
    public function loadByIds(int $storeId, array $productIds, array $attributeCodes, bool $withGallery): array
    {
        if (!$productIds) {
            return [];
        }
        $collection = $this->createCollection($storeId, $attributeCodes);
        $collection->addFieldToFilter('entity_id', ['in' => $productIds]);

        $products = [];
        foreach ($this->fetch($collection, $withGallery) as $product) {
            $products[(int) $product->getId()] = $product;
        }

        return $products;
    }

    /**
     * Collection of enabled products assigned to the store's website.
     *
     * @param int $storeId
     * @param string[] $attributeCodes
     * @return Collection
     */
    private function createCollection(int $storeId, array $attributeCodes): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->addAttributeToSelect($this->filterKnown(array_merge(self::BASE_ATTRIBUTES, $attributeCodes)))
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addUrlRewrite()
            ->addTierPriceData();

        return $collection;
    }

    /**
     * Load the collection and prepare each product for anonymous pricing.
     *
     * @param Collection $collection
     * @param bool $withGallery
     * @return Product[]
     */
    private function fetch(Collection $collection, bool $withGallery): array
    {
        $collection->load();
        if ($withGallery) {
            $collection->addMediaGalleryData();
        }

        $products = [];
        foreach ($collection->getItems() as $product) {
            $product->setCustomerGroupId(0);
            $products[] = $product;
        }

        return $products;
    }

    /**
     * Drop codes that are not product attributes; selecting an unknown one would fail the whole load.
     *
     * @param string[] $codes
     * @return string[]
     */
    private function filterKnown(array $codes): array
    {
        $known = [];
        foreach (array_unique($codes) as $code) {
            $this->knownAttributes[$code] ??= (bool) $this->eavConfig->getAttribute(Product::ENTITY, $code)->getId();
            if ($this->knownAttributes[$code]) {
                $known[] = $code;
            }
        }

        return $known;
    }
}
