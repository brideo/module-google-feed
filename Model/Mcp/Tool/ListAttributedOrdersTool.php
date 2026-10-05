<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Mcp\Tool;

use UpturnStudio\GoogleFeed\Api\AttributedOrderListInterface;
use UpturnStudio\GoogleFeed\Model\Api\DataConverter;
use UpturnStudio\GoogleFeed\Model\Mcp\AbstractTool;
use UpturnStudio\GoogleFeed\Model\Mcp\ToolGuard;

/**
 * MCP tool: google_feed_list_attributed_orders.
 */
class ListAttributedOrdersTool extends AbstractTool
{
    /**
     * @param ToolGuard $guard
     * @param AttributedOrderListInterface $orderList
     * @param DataConverter $converter
     */
    public function __construct(
        ToolGuard $guard,
        private readonly AttributedOrderListInterface $orderList,
        private readonly DataConverter $converter
    ) {
        parent::__construct($guard);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'google_feed_list_attributed_orders';
    }

    /**
     * @inheritdoc
     */
    protected function description(): string
    {
        return 'Lists orders that came from a Google Ads click, with the click ID, the landing SKU and the purchased items (quantities, totals, refunds and cost). Oldest update first, for syncing incrementally.';
    }

    /**
     * @inheritdoc
     */
    protected function inputSchema(): array
    {
        return $this->object(['updated_from' => ['type' => 'string', 'description' => 'ISO 8601 UTC time; only orders updated since.'], 'page_size' => ['type' => 'integer', 'description' => 'Default 100, at most 500.'], 'current_page' => ['type' => 'integer']]);
    }

    /**
     * @inheritdoc
     */
    protected function aclResource(): string
    {
        return self::ACL_ORDERS;
    }

    /**
     * @inheritdoc
     */
    protected function changesData(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function run(array $arguments): array
    {
        return $this->converter->toArray($this->orderList->getList(
            $arguments['updated_from'] ?? null,
            (int) ($arguments['page_size'] ?? 100),
            (int) ($arguments['current_page'] ?? 1)
        ));
    }
}
