<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Mcp\Tool;

use UpturnStudio\GoogleFeed\Model\Management\FeedService;
use UpturnStudio\GoogleFeed\Model\Mcp\AbstractTool;
use UpturnStudio\GoogleFeed\Model\Mcp\ToolGuard;

/**
 * MCP tool: google_feed_set_mapping_rows.
 */
class SetMappingRowsTool extends AbstractTool
{
    /**
     * @param ToolGuard $guard
     * @param FeedService $feedService
     */
    public function __construct(
        ToolGuard $guard,
        private readonly FeedService $feedService
    ) {
        parent::__construct($guard);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'google_feed_set_mapping_rows';
    }

    /**
     * @inheritdoc
     */
    protected function description(): string
    {
        return 'Adds or replaces mapping rows on a feed by Google attribute, leaving its other rows as they are. Use google_feed_preview_item to check the effect on a SKU first.';
    }

    /**
     * @inheritdoc
     */
    protected function inputSchema(): array
    {
        return $this->object(['feed_id' => ['type' => 'integer'], 'rows' => ['type' => 'array', 'items' => $this->mappingRowSchema()]], ['feed_id', 'rows']);
    }

    /**
     * @inheritdoc
     */
    protected function aclResource(): string
    {
        return self::ACL_FEEDS_MANAGE;
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
        return $this->feedService->setMappingRows(
            $this->intArgument($arguments, 'feed_id'),
            (array) ($arguments['rows'] ?? [])
        );
    }
}
