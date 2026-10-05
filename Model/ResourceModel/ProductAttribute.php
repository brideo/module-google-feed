<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Storage for Google attribute values pushed per SKU, outside of EAV.
 */
class ProductAttribute
{
    public const TABLE = 'upturnstudio_googlefeed_product_attribute';

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * Insert or update values; each row needs sku, store_id, attribute_code, value and source.
     *
     * @param array[] $rows
     * @return void
     */
    public function upsert(array $rows): void
    {
        if (!$rows) {
            return;
        }
        $this->resource->getConnection()->insertOnDuplicate(
            $this->resource->getTableName(self::TABLE),
            $rows,
            ['value', 'source']
        );
    }

    /**
     * Delete one value.
     *
     * @param string $sku
     * @param int $storeId
     * @param string $attributeCode
     * @return int Rows removed
     */
    public function delete(string $sku, int $storeId, string $attributeCode): int
    {
        return (int) $this->resource->getConnection()->delete(
            $this->resource->getTableName(self::TABLE),
            ['sku = ?' => $sku, 'store_id = ?' => $storeId, 'attribute_code = ?' => $attributeCode]
        );
    }

    /**
     * Effective values for a store: store-specific rows win over the all-stores rows.
     *
     * @param string[] $skus
     * @param int $storeId
     * @return string[][] Lower-cased SKU => Google attribute code => value
     */
    public function getEffectiveValues(array $skus, int $storeId): array
    {
        if (!$skus) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), ['sku', 'store_id', 'attribute_code', 'value'])
            ->where('sku IN (?)', $skus)
            ->where('store_id IN (?)', [0, $storeId], \Zend_Db::INT_TYPE)
            ->order('store_id ASC');

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $result[mb_strtolower((string) $row['sku'])][(string) $row['attribute_code']] = (string) $row['value'];
        }

        return $result;
    }

    /**
     * Every stored row for one SKU, all stores first.
     *
     * @param string $sku
     * @return array[]
     */
    public function getBySku(string $sku): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE))
            ->where('sku = ?', $sku)
            ->order(['store_id ASC', 'attribute_code ASC']);

        return $connection->fetchAll($select);
    }

    /**
     * Page of stored rows, oldest update first.
     *
     * @param string|null $sku
     * @param int|null $storeId
     * @param string|null $updatedFrom
     * @param int $pageSize
     * @param int $currentPage
     * @return array[] With keys "rows" and "total"
     */
    public function getPage(?string $sku, ?int $storeId, ?string $updatedFrom, int $pageSize, int $currentPage): array
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName(self::TABLE);
        $select = $connection->select()->from($table);
        if ($sku !== null && $sku !== '') {
            $select->where('sku = ?', $sku);
        }
        if ($storeId !== null) {
            $select->where('store_id = ?', $storeId);
        }
        if ($updatedFrom !== null && $updatedFrom !== '') {
            $select->where('updated_at >= ?', $updatedFrom);
        }

        $count = clone $select;
        $count->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(['COUNT(*)']);
        $total = (int) $connection->fetchOne($count);

        $select->order(['updated_at ASC', 'value_id ASC'])->limitPage($currentPage, $pageSize);

        return ['rows' => $connection->fetchAll($select), 'total' => $total];
    }
}
