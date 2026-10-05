<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api\Data;

/**
 * A page of click-attributed orders.
 *
 * @api
 */
interface AttributedOrderSearchResultsInterface
{
    /**
     * Orders on this page.
     *
     * @return \UpturnStudio\GoogleFeed\Api\Data\AttributedOrderInterface[]
     */
    public function getItems(): array;

    /**
     * Number of orders matching the filters.
     *
     * @return int
     */
    public function getTotalCount(): int;
}
