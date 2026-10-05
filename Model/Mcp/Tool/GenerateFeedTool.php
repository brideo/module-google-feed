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
 * MCP tool: google_feed_generate_feed.
 */
class GenerateFeedTool extends AbstractTool
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
        return 'google_feed_generate_feed';
    }

    /**
     * @inheritdoc
     */
    protected function description(): string
    {
        return 'Queues a feed for generation. The file is written on the next cron run; read the feed with google_feed_get_feed to follow last_status (queued, running, success or error).';
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
        return $this->feedService->generate($this->intArgument($arguments, 'feed_id'));
    }
}
