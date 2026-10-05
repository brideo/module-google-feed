<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Typed accessors for the module's store configuration.
 */
class Config
{
    public const XML_PATH_BATCH_SIZE = 'upturnstudio_googlefeed/generation/batch_size';
    public const XML_PATH_TRACKING_ENABLED = 'upturnstudio_googlefeed/tracking/enabled';
    public const XML_PATH_COOKIE_LIFETIME = 'upturnstudio_googlefeed/tracking/cookie_lifetime';
    public const XML_PATH_RESPECT_RESTRICTION = 'upturnstudio_googlefeed/tracking/respect_cookie_restriction';
    public const XML_PATH_SUBSCRIPTION_KEY = 'upturnstudio_googlefeed/subscription/key';
    public const XML_PATH_VALIDATION_URL = 'upturnstudio_googlefeed/subscription/validation_url';
    public const XML_PATH_MCP_ALLOW_WRITE = 'upturnstudio_googlefeed/mcp/allow_write';
    public const XML_PATH_COOKIE_RESTRICTION = 'web/cookie/cookie_restriction';
    public const XML_PATH_WEIGHT_UNIT = 'general/locale/weight_unit';

    private const DEFAULT_BATCH_SIZE = 500;
    private const DEFAULT_COOKIE_LIFETIME = 90;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Chunk size used while a feed is written.
     *
     * @return int
     */
    public function getBatchSize(): int
    {
        $value = (int) $this->scopeConfig->getValue(self::XML_PATH_BATCH_SIZE);

        return $value > 0 ? $value : self::DEFAULT_BATCH_SIZE;
    }

    /**
     * Whether click IDs are captured on the given store.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isTrackingEnabled(int|string|null $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_TRACKING_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Attribution window in days.
     *
     * @param int|string|null $storeId
     * @return int
     */
    public function getCookieLifetimeDays(int|string|null $storeId = null): int
    {
        $value = (int) $this->scopeConfig->getValue(
            self::XML_PATH_COOKIE_LIFETIME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $value > 0 ? $value : self::DEFAULT_COOKIE_LIFETIME;
    }

    /**
     * Whether capture must wait for the shopper to accept cookies.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isConsentRequired(int|string|null $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_RESPECT_RESTRICTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) && $this->scopeConfig->isSetFlag(self::XML_PATH_COOKIE_RESTRICTION, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Whether the "respect cookie restriction" setting is on, whatever the store's own restriction setting is.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isRespectCookieRestriction(int|string|null $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_RESPECT_RESTRICTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Whether AI connector (MCP) tools may change data. Off unless an admin turns it on.
     *
     * @return bool
     */
    public function isMcpWriteAllowed(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_MCP_ALLOW_WRITE);
    }

    /**
     * Decrypted subscription key, empty when not set.
     *
     * @return string
     */
    public function getSubscriptionKey(): string
    {
        return trim((string) $this->scopeConfig->getValue(self::XML_PATH_SUBSCRIPTION_KEY));
    }

    /**
     * Endpoint the subscription key is validated against, empty when none is configured.
     *
     * @return string
     */
    public function getValidationUrl(): string
    {
        return trim((string) $this->scopeConfig->getValue(self::XML_PATH_VALIDATION_URL));
    }

    /**
     * Google weight unit (lb or kg) for the given store.
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getWeightUnit(int|string|null $storeId = null): string
    {
        $unit = (string) $this->scopeConfig->getValue(self::XML_PATH_WEIGHT_UNIT, ScopeInterface::SCOPE_STORE, $storeId);

        return $unit === 'kgs' ? 'kg' : 'lb';
    }
}
