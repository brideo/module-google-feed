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
 * MCP tool: google_feed_update_settings.
 */
class UpdateSettingsTool extends AbstractTool
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
        return 'google_feed_update_settings';
    }

    /**
     * @inheritdoc
     */
    protected function description(): string
    {
        return 'Changes click capture and generation settings. Only the fields given change. Without store_id the default changes; batch_size is global and cannot be set for a store view.';
    }

    /**
     * @inheritdoc
     */
    protected function inputSchema(): array
    {
        return $this->object(['tracking_enabled' => ['type' => 'boolean'], 'cookie_lifetime_days' => ['type' => 'integer'], 'respect_cookie_restriction' => ['type' => 'boolean'], 'batch_size' => ['type' => 'integer'], 'store_id' => ['type' => 'integer']]);
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
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function run(array $arguments): array
    {
        $storeId = isset($arguments['store_id']) ? (int) $arguments['store_id'] : null;
        unset($arguments['store_id']);

        return $this->settingsService->update($arguments, $storeId);
    }
}
