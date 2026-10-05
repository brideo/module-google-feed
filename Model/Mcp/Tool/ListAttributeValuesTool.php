<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Mcp\Tool;

use UpturnStudio\GoogleFeed\Api\ProductAttributeManagementInterface;
use UpturnStudio\GoogleFeed\Model\Api\DataConverter;
use UpturnStudio\GoogleFeed\Model\Mcp\AbstractTool;
use UpturnStudio\GoogleFeed\Model\Mcp\ToolGuard;

/**
 * MCP tool: google_feed_list_attribute_values.
 */
class ListAttributeValuesTool extends AbstractTool
{
    /**
     * @param ToolGuard $guard
     * @param ProductAttributeManagementInterface $management
     * @param DataConverter $converter
     */
    public function __construct(
        ToolGuard $guard,
        private readonly ProductAttributeManagementInterface $management,
        private readonly DataConverter $converter
    ) {
        parent::__construct($guard);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'google_feed_list_attribute_values';
    }

    /**
     * @inheritdoc
     */
    protected function description(): string
    {
        return 'Lists Google attribute values stored per SKU (such as google_product_category, gtin or custom_label_0). These override the feed mapping.';
    }

    /**
     * @inheritdoc
     */
    protected function inputSchema(): array
    {
        return $this->object(['sku' => ['type' => 'string'], 'store_id' => ['type' => 'integer'], 'updated_from' => ['type' => 'string', 'description' => 'ISO 8601 UTC time; only values updated since.'], 'page_size' => ['type' => 'integer', 'description' => 'Default 200, at most 1000.'], 'current_page' => ['type' => 'integer']]);
    }

    /**
     * @inheritdoc
     */
    protected function aclResource(): string
    {
        return self::ACL_ATTRIBUTES;
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
        return $this->converter->toArray($this->management->getList(
            $arguments['sku'] ?? null,
            isset($arguments['store_id']) ? (int) $arguments['store_id'] : null,
            $arguments['updated_from'] ?? null,
            (int) ($arguments['page_size'] ?? 200),
            (int) ($arguments['current_page'] ?? 1)
        ));
    }
}
