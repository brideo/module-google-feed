<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api;

use UpturnStudio\GoogleFeed\Api\Data\AttributedOrderSearchResultsInterface;

/**
 * Lists orders that carry a captured Google Ads click.
 *
 * @api
 */
interface AttributedOrderListInterface
{
    public const MAX_PAGE_SIZE = 500;

    /**
     * Click-attributed orders, oldest update first, for incremental syncing.
     *
     * @param string|null $updatedFrom Only orders updated at or after this UTC time
     * @param int $pageSize
     * @param int $currentPage
     * @return \UpturnStudio\GoogleFeed\Api\Data\AttributedOrderSearchResultsInterface
     */
    public function getList(
        ?string $updatedFrom = null,
        int $pageSize = 100,
        int $currentPage = 1
    ): AttributedOrderSearchResultsInterface;
}
