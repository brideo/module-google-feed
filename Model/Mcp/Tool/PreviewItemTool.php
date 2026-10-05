<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Mcp\Tool;

use UpturnStudio\GoogleFeed\Model\Management\ItemPreviewService;
use UpturnStudio\GoogleFeed\Model\Mcp\AbstractTool;
use UpturnStudio\GoogleFeed\Model\Mcp\ToolGuard;

/**
 * MCP tool: google_feed_preview_item.
 */
class PreviewItemTool extends AbstractTool
{
    /**
     * @param ToolGuard $guard
     * @param ItemPreviewService $previewService
     */
    public function __construct(
        ToolGuard $guard,
        private readonly ItemPreviewService $previewService
    ) {
        parent::__construct($guard);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'google_feed_preview_item';
    }

    /**
     * @inheritdoc
     */
    protected function description(): string
    {
        return 'Shows exactly what a SKU would look like in a feed, or why it would not be listed. Give mapping to try rows that are not saved yet, so a change can be checked before it is made.';
    }

    /**
     * @inheritdoc
     */
    protected function inputSchema(): array
    {
        return $this->object(['feed_id' => ['type' => 'integer'], 'sku' => ['type' => 'string', 'description' => 'A simple product or a variant of a configurable product.'], 'mapping' => ['type' => 'array', 'items' => $this->mappingRowSchema(), 'description' => 'Complete mapping to try instead of the feed\'s own.']], ['feed_id', 'sku']);
    }

    /**
     * @inheritdoc
     */
    protected function aclResource(): string
    {
        return self::ACL_FEEDS;
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
        return $this->previewService->preview(
            $this->intArgument($arguments, 'feed_id'),
            $this->stringArgument($arguments, 'sku'),
            isset($arguments['mapping']) ? (array) $arguments['mapping'] : null
        );
    }
}
