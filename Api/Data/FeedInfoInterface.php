<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api\Data;

/**
 * A feed and where its file can be fetched.
 *
 * @api
 */
interface FeedInfoInterface
{
    /**
     * Feed ID.
     *
     * @return int
     */
    public function getFeedId(): int;

    /**
     * Feed name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Store view ID.
     *
     * @return int
     */
    public function getStoreId(): int;

    /**
     * Store view code.
     *
     * @return string
     */
    public function getStoreCode(): string;

    /**
     * Target country code.
     *
     * @return string|null
     */
    public function getTargetCountry(): ?string;

    /**
     * Currency prices are listed in.
     *
     * @return string
     */
    public function getCurrencyCode(): string;

    /**
     * Public URL of the feed file.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Whether the feed is generated on schedule.
     *
     * @return bool
     */
    public function getIsActive(): bool;

    /**
     * Status of the last run.
     *
     * @return string|null
     */
    public function getLastStatus(): ?string;

    /**
     * Time of the last successful run (UTC).
     *
     * @return string|null
     */
    public function getLastGeneratedAt(): ?string;

    /**
     * Items written by the last successful run.
     *
     * @return int|null
     */
    public function getProductCount(): ?int;
}
