<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api\Data;

/**
 * A page of stored Google attribute values.
 *
 * @api
 */
interface ProductAttributeValueSearchResultsInterface
{
    /**
     * Values on this page.
     *
     * @return \UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueInterface[]
     */
    public function getItems(): array;

    /**
     * Number of values matching the filters.
     *
     * @return int
     */
    public function getTotalCount(): int;
}
