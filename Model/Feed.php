<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model;

use Magento\Framework\Model\AbstractModel;
use UpturnStudio\GoogleFeed\Model\ResourceModel\Feed as FeedResource;

/**
 * A Google Merchant Center feed definition: store, filters, attribute mapping and schedule.
 */
class Feed extends AbstractModel
{
    public const DIRECTORY = 'upturnstudio/googlefeed';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';

    public const PRICE_TAX_AUTO = 'auto';
    public const PRICE_TAX_INCLUDING = 'incl';
    public const PRICE_TAX_EXCLUDING = 'excl';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(FeedResource::class);
    }

    /**
     * Feed name.
     *
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->getData('name');
    }

    /**
     * Store view the feed is generated for.
     *
     * @return int
     */
    public function getStoreId(): int
    {
        return (int) $this->getData('store_id');
    }

    /**
     * Whether the feed is generated on schedule.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->getData('is_active');
    }

    /**
     * Unguessable token the public file is named after.
     *
     * @return string
     */
    public function getToken(): string
    {
        return (string) $this->getData('token');
    }

    /**
     * Price tax mode: auto, incl or excl.
     *
     * @return string
     */
    public function getPriceTax(): string
    {
        return (string) ($this->getData('price_tax') ?: self::PRICE_TAX_AUTO);
    }

    /**
     * Cron expression, empty when the feed is only generated manually.
     *
     * @return string
     */
    public function getCronExpression(): string
    {
        return trim((string) $this->getData('cron_expression'));
    }

    /**
     * Decoded product filters.
     *
     * @return array
     */
    public function getFilters(): array
    {
        return $this->decode($this->getData('filters'));
    }

    /**
     * Decoded mapping rows, each with google_attribute, source, value, fallback and use_parent.
     *
     * @return array[]
     */
    public function getMapping(): array
    {
        return array_values(array_filter($this->decode($this->getData('mapping')), 'is_array'));
    }

    /**
     * Path of the generated file relative to the media directory.
     *
     * @return string
     */
    public function getRelativePath(): string
    {
        return self::DIRECTORY . '/' . $this->getToken() . '.xml';
    }

    /**
     * Decode a JSON column that may already be an array.
     *
     * @param mixed $value
     * @return array
     */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
