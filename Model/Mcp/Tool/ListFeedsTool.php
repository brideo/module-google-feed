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
 * MCP tool: google_feed_list_feeds.
 */
class ListFeedsTool extends AbstractTool
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
        return 'google_feed_list_feeds';
    }

    /**
     * @inheritdoc
     */
    protected function description(): string
    {
        return 'Lists all Google Merchant Center feeds with their store view, schedule, last run status and feed URL.';
    }

    /**
     * @inheritdoc
     */
    protected function inputSchema(): array
    {
        return $this->object([]);
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
        return ['feeds' => $this->feedService->getList()];
    }
}
