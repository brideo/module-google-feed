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
 * MCP tool: google_feed_delete_attribute_values.
 */
class DeleteAttributeValuesTool extends AbstractTool
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
        return 'google_feed_delete_attribute_values';
    }

    /**
     * @inheritdoc
     */
    protected function description(): string
    {
        return 'Removes stored Google attribute values by SKU, store_id and attribute_code, so the feed mapping applies again.';
    }

    /**
     * @inheritdoc
     */
    protected function inputSchema(): array
    {
        return $this->object(['items' => ['type' => 'array', 'items' => $this->object([
                        'sku' => ['type' => 'string'],
                        'attribute_code' => ['type' => 'string', 'description' => 'Google attribute code, e.g. google_product_category.'],
                        'value' => ['type' => 'string', 'description' => 'Empty removes the attribute from the feed item.'],
                        'store_id' => ['type' => 'integer', 'description' => '0 or omitted for every store view.'],
                        'source' => ['type' => 'string', 'description' => 'A label for whoever wrote the value.'],
                    ], ['sku', 'attribute_code'])]], ['items']);
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
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function run(array $arguments): array
    {
        return $this->converter->toArray(
            $this->management->delete($this->converter->attributeValues((array) ($arguments['items'] ?? [])))
        );
    }
}
