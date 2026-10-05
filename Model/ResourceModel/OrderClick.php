<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Storage for the ad click recorded against an order.
 */
class OrderClick
{
    public const TABLE = 'upturnstudio_googlefeed_order_click';

    private const CONNECTION = 'sales';

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * Record the click for an order; a second call for the same order replaces the first.
     *
     * @param int $orderId
     * @param array $click With keys gclid, gbraid, wbraid, landing_sku, landing_url and clicked_at
     * @return void
     */
    public function save(int $orderId, array $click): void
    {
        $columns = ['gclid', 'gbraid', 'wbraid', 'landing_sku', 'landing_url', 'clicked_at'];
        $row = ['order_id' => $orderId];
        foreach ($columns as $column) {
            $row[$column] = $click[$column] ?? null;
        }

        $this->resource->getConnection(self::CONNECTION)->insertOnDuplicate(
            $this->resource->getTableName(self::TABLE, self::CONNECTION),
            $row,
            $columns
        );
    }
}
