<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api\Data;

use Magento\Framework\DataObject;
use UpturnStudio\GoogleFeed\Api\Data\FeedInfoInterface;

/**
 * A feed and where its file can be fetched.
 */
class FeedInfo extends DataObject implements FeedInfoInterface
{
    /**
     * @inheritdoc
     */
    public function getFeedId(): int
    {
        return (int) $this->getData('feed_id');
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return (string) $this->getData('name');
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): int
    {
        return (int) $this->getData('store_id');
    }

    /**
     * @inheritdoc
     */
    public function getStoreCode(): string
    {
        return (string) $this->getData('store_code');
    }

    /**
     * @inheritdoc
     */
    public function getTargetCountry(): ?string
    {
        return $this->getData('target_country') === null ? null : (string) $this->getData('target_country');
    }

    /**
     * @inheritdoc
     */
    public function getCurrencyCode(): string
    {
        return (string) $this->getData('currency_code');
    }

    /**
     * @inheritdoc
     */
    public function getUrl(): string
    {
        return (string) $this->getData('url');
    }

    /**
     * @inheritdoc
     */
    public function getIsActive(): bool
    {
        return (bool) $this->getData('is_active');
    }

    /**
     * @inheritdoc
     */
    public function getLastStatus(): ?string
    {
        return $this->getData('last_status') === null ? null : (string) $this->getData('last_status');
    }

    /**
     * @inheritdoc
     */
    public function getLastGeneratedAt(): ?string
    {
        return $this->getData('last_generated_at') === null ? null : (string) $this->getData('last_generated_at');
    }

    /**
     * @inheritdoc
     */
    public function getProductCount(): ?int
    {
        return $this->getData('product_count') === null ? null : (int) $this->getData('product_count');
    }
}
