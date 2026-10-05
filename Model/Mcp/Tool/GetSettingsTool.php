<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Mcp\Tool;

use UpturnStudio\GoogleFeed\Model\Management\SettingsService;
use UpturnStudio\GoogleFeed\Model\Mcp\AbstractTool;
use UpturnStudio\GoogleFeed\Model\Mcp\ToolGuard;

/**
 * MCP tool: google_feed_get_settings.
 */
class GetSettingsTool extends AbstractTool
{
    /**
     * @param ToolGuard $guard
     * @param SettingsService $settingsService
     */
    public function __construct(
        ToolGuard $guard,
        private readonly SettingsService $settingsService
    ) {
        parent::__construct($guard);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'google_feed_get_settings';
    }

    /**
     * @inheritdoc
     */
    protected function description(): string
    {
        return 'Returns the click capture and generation settings, as they apply to a store view when store_id is given.';
    }

    /**
     * @inheritdoc
     */
    protected function inputSchema(): array
    {
        return $this->object(['store_id' => ['type' => 'integer']]);
    }

    /**
     * @inheritdoc
     */
    protected function aclResource(): string
    {
        return self::ACL_SETTINGS;
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
        return $this->settingsService->get(isset($arguments['store_id']) ? (int) $arguments['store_id'] : null);
    }
}
