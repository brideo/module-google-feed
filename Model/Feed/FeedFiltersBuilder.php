<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

use UpturnStudio\GoogleFeed\Model\Feed;
use UpturnStudio\GoogleFeed\Model\ResourceModel\CatalogData;

/**
 * Turns a feed's stored filters into the values the generator and the preview apply.
 */
class FeedFiltersBuilder
{
    /**
     * @param CatalogData $catalogData
     */
    public function __construct(
        private readonly CatalogData $catalogData
    ) {
    }

    /**
     * Build the filters, expanding categories to include their descendants.
     *
     * @param Feed $feed
     * @return FeedFilters
     */
    public function build(Feed $feed): FeedFilters
    {
        $filters = $feed->getFilters();

        return new FeedFilters(
            array_values(array_filter((array) ($filters['product_types'] ?? []))) ?: Generator::DEFAULT_TYPES,
            array_map('intval', array_filter((array) ($filters['attribute_set_ids'] ?? []))),
            $this->catalogData->expandCategoryIds((array) ($filters['category_ids'] ?? [])),
            array_map('intval', array_filter((array) ($filters['visibility'] ?? []))),
            !empty($filters['exclude_out_of_stock'])
        );
    }
}
