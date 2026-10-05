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
    public const QUOTE_TABLE = 'upturnstudio_googlefeed_quote_click';

    private const CONNECTION = 'sales';
    private const QUOTE_CONNECTION = 'checkout';
    private const COLUMNS = ['gclid', 'gbraid', 'wbraid', 'landing_sku', 'landing_url', 'clicked_at'];

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

    /**
     * Attach a click to a cart, for storefronts that cannot send the click cookie to Magento.
     *
     * @param int $quoteId
     * @param array $click With keys gclid, gbraid, wbraid, landing_sku, landing_url and clicked_at
     * @return void
     */
    public function saveQuoteClick(int $quoteId, array $click): void
    {
        $row = ['quote_id' => $quoteId];
        foreach (self::COLUMNS as $column) {
            $row[$column] = $click[$column] ?? null;
        }

        $this->resource->getConnection(self::QUOTE_CONNECTION)->insertOnDuplicate(
            $this->resource->getTableName(self::QUOTE_TABLE, self::QUOTE_CONNECTION),
            $row,
            self::COLUMNS
        );
    }

    /**
     * The click attached to a cart, null when there is none.
     *
     * @param int $quoteId
     * @return array|null
     */
    public function getQuoteClick(int $quoteId): ?array
    {
        $connection = $this->resource->getConnection(self::QUOTE_CONNECTION);
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->resource->getTableName(self::QUOTE_TABLE, self::QUOTE_CONNECTION), self::COLUMNS)
                ->where('quote_id = ?', $quoteId)
        );

        return $row ?: null;
    }
}
