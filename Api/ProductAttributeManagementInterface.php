<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api;

use UpturnStudio\GoogleFeed\Api\Data\BulkResultInterface;
use UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueSearchResultsInterface;

/**
 * Reads and writes Google attribute values per SKU.
 *
 * @api
 */
interface ProductAttributeManagementInterface
{
    public const MAX_ITEMS_PER_REQUEST = 1000;

    /**
     * Create or replace values. Invalid items are reported and skipped; valid ones are still written.
     *
     * @param \UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueInterface[] $items
     * @return \UpturnStudio\GoogleFeed\Api\Data\BulkResultInterface
     */
    public function save(array $items): BulkResultInterface;

    /**
     * Remove values, identified by SKU, store ID and attribute code.
     *
     * @param \UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueInterface[] $items
     * @return \UpturnStudio\GoogleFeed\Api\Data\BulkResultInterface
     */
    public function delete(array $items): BulkResultInterface;

    /**
     * Stored values, oldest update first.
     *
     * @param string|null $sku
     * @param int|null $storeId
     * @param string|null $updatedFrom
     * @param int $pageSize
     * @param int $currentPage
     * @return \UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueSearchResultsInterface
     */
    public function getList(
        ?string $sku = null,
        ?int $storeId = null,
        ?string $updatedFrom = null,
        int $pageSize = 200,
        int $currentPage = 1
    ): ProductAttributeValueSearchResultsInterface;
}
