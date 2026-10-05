<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

use Magento\Catalog\Model\Product\Visibility;

/**
 * Decides whether a product is listed in a feed, and says why not when it is not.
 */
class ListingRules
{
    /**
     * Why the product is left out of the feed, or null when it is listed.
     *
     * A variant without its own page is judged by its parent's visibility and is dropped when it has no parent.
     *
     * @param ProductContext $context
     * @param FeedFilters $filters
     * @return string|null
     */
    public function getExclusionReason(ProductContext $context, FeedFilters $filters): ?string
    {
        if ($filters->inStockOnly && !$context->salable) {
            return 'It cannot be bought and the feed leaves out unavailable products.';
        }
        if (!$context->isVisibleIndividually() && $context->parent === null) {
            return 'It is not visible individually and is not a variant of an enabled configurable product.';
        }
        $visibility = (int) $context->getPageProduct()->getVisibility();
        if ($visibility === Visibility::VISIBILITY_NOT_VISIBLE) {
            return 'It is not visible on the storefront.';
        }
        if ($filters->visibilities && !in_array($visibility, $filters->visibilities, true)) {
            return 'Its visibility is not one the feed includes.';
        }
        if ($filters->categoryIds && !array_intersect($context->categoryIds, $filters->categoryIds)) {
            return 'It is not in a category the feed includes.';
        }

        return null;
    }
}
