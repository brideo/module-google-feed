<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Batched catalogue lookups the feed generator needs for a chunk of products.
 */
class CatalogData
{
    /**
     * @param ResourceConnection $resource
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly MetadataPool $metadataPool
    ) {
    }

    /**
     * Configurable parents of the given products.
     *
     * @param int[] $childIds
     * @return int[][] Child entity ID => parent entity IDs
     */
    public function getParentIdsByChildIds(array $childIds): array
    {
        if (!$childIds) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $select = $connection->select()
            ->from(['l' => $this->resource->getTableName('catalog_product_super_link')], ['product_id'])
            ->join(
                ['e' => $this->resource->getTableName('catalog_product_entity')],
                'e.' . $linkField . ' = l.parent_id',
                ['parent_entity_id' => 'entity_id']
            )
            ->where('l.product_id IN (?)', $childIds, \Zend_Db::INT_TYPE)
            ->order('e.entity_id ASC');

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $result[(int) $row['product_id']][] = (int) $row['parent_entity_id'];
        }

        return $result;
    }

    /**
     * Category assignments of the given products.
     *
     * @param int[] $productIds
     * @return int[][] Product entity ID => category IDs
     */
    public function getCategoryIdsByProductIds(array $productIds): array
    {
        if (!$productIds) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName('catalog_category_product'), ['product_id', 'category_id'])
            ->where('product_id IN (?)', $productIds, \Zend_Db::INT_TYPE);

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $result[(int) $row['product_id']][] = (int) $row['category_id'];
        }

        return $result;
    }

    /**
     * The given categories together with all of their descendants.
     *
     * @param int[] $categoryIds
     * @return int[]
     */
    public function expandCategoryIds(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
        if (!$categoryIds) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $conditions = [$connection->quoteInto('entity_id IN (?)', $categoryIds, \Zend_Db::INT_TYPE)];
        foreach ($categoryIds as $categoryId) {
            $conditions[] = $connection->quoteInto('path LIKE ?', '%/' . $categoryId . '/%');
        }
        $select = $connection->select()
            ->from($this->resource->getTableName('catalog_category_entity'), ['entity_id'])
            ->where(implode(' OR ', $conditions));

        return array_map('intval', $connection->fetchCol($select));
    }
}
