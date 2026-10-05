<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api;

/**
 * Lists the store's feeds, so a reporting system can match Google Ads item IDs to a store and currency.
 *
 * @api
 */
interface FeedListInterface
{
    /**
     * All feeds.
     *
     * @return \UpturnStudio\GoogleFeed\Api\Data\FeedInfoInterface[]
     */
    public function getList(): array;
}
