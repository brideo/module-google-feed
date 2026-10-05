<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Mcp\Tool;

use UpturnStudio\GoogleFeed\Model\Management\DiscoveryService;
use UpturnStudio\GoogleFeed\Model\Mcp\AbstractTool;
use UpturnStudio\GoogleFeed\Model\Mcp\ToolGuard;

/**
 * MCP tool: google_feed_get_options.
 */
class GetOptionsTool extends AbstractTool
{
    /**
     * @param ToolGuard $guard
     * @param DiscoveryService $discoveryService
     */
    public function __construct(
        ToolGuard $guard,
        private readonly DiscoveryService $discoveryService
    ) {
        parent::__construct($guard);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'google_feed_get_options';
    }

    /**
     * @inheritdoc
     */
    protected function description(): string
    {
        return 'Lists everything a feed can be built from: Google attributes, mapping sources (static, template, built-in values and product attributes), product types, visibilities, price tax modes, store views and the default mapping. Call this first when creating or editing a feed.';
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
        return $this->discoveryService->get();
    }
}
