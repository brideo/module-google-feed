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
 * MCP tool: google_feed_remove_mapping_rows.
 */
class RemoveMappingRowsTool extends AbstractTool
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
        return 'google_feed_remove_mapping_rows';
    }

    /**
     * @inheritdoc
     */
    protected function description(): string
    {
        return 'Removes mapping rows from a feed by Google attribute. Attributes that are not mapped are ignored.';
    }

    /**
     * @inheritdoc
     */
    protected function inputSchema(): array
    {
        return $this->object(['feed_id' => ['type' => 'integer'], 'attributes' => ['type' => 'array', 'items' => ['type' => 'string']]], ['feed_id', 'attributes']);
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
        return $this->feedService->removeMappingRows(
            $this->intArgument($arguments, 'feed_id'),
            (array) ($arguments['attributes'] ?? [])
        );
    }
}
