<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

/**
 * A feed's product filters in the form the generator and the item preview apply them.
 */
class FeedFilters
{
    /**
     * @param string[] $typeIds Product types to list
     * @param int[] $attributeSetIds Empty for any
     * @param int[] $categoryIds The chosen categories and everything beneath them; empty for any
     * @param int[] $visibilities Empty for any storefront visibility
     * @param bool $inStockOnly
     */
    public function __construct(
        public readonly array $typeIds,
        public readonly array $attributeSetIds,
        public readonly array $categoryIds,
        public readonly array $visibilities,
        public readonly bool $inStockOnly
    ) {
    }
}
