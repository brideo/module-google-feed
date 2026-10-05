<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Store\Api\Data\StoreInterface;
use UpturnStudio\GoogleFeed\Model\Feed;

/**
 * Everything a mapping source needs to produce a value for one feed item.
 */
class ProductContext
{
    /**
     * @param Product $product The product the item is written for
     * @param Product|null $parent Its configurable parent, when it is a variant
     * @param StoreInterface $store
     * @param Feed $feed
     * @param bool $salable
     * @param int[] $categoryIds Categories of the product and its parent
     */
    public function __construct(
        public readonly Product $product,
        public readonly ?Product $parent,
        public readonly StoreInterface $store,
        public readonly Feed $feed,
        public readonly bool $salable,
        public readonly array $categoryIds = []
    ) {
    }

    /**
     * Whether the product has its own storefront page.
     *
     * @return bool
     */
    public function isVisibleIndividually(): bool
    {
        return (int) $this->product->getVisibility() !== Visibility::VISIBILITY_NOT_VISIBLE;
    }

    /**
     * The product whose page and imagery represent this item on the storefront.
     *
     * @return Product
     */
    public function getPageProduct(): Product
    {
        return $this->parent !== null && !$this->isVisibleIndividually() ? $this->parent : $this->product;
    }
}
