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
 * MCP tool: google_feed_get_feed.
 */
class GetFeedTool extends AbstractTool
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
        return 'google_feed_get_feed';
    }

    /**
     * @inheritdoc
     */
    protected function description(): string
    {
        return 'Returns one feed in full: filters, attribute mapping, schedule, URL, and the status and error of its last run.';
    }

    /**
     * @inheritdoc
     */
    protected function inputSchema(): array
    {
        return $this->object(['feed_id' => ['type' => 'integer', 'description' => 'Feed ID; see google_feed_list_feeds.']], ['feed_id']);
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
        return $this->feedService->get($this->intArgument($arguments, 'feed_id'));
    }
}
