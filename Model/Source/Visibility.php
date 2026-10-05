<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;

/**
 * Storefront visibilities a feed can be limited to.
 */
class Visibility implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => ProductVisibility::VISIBILITY_IN_CATALOG, 'label' => __('Catalog')],
            ['value' => ProductVisibility::VISIBILITY_IN_SEARCH, 'label' => __('Search')],
            ['value' => ProductVisibility::VISIBILITY_BOTH, 'label' => __('Catalog, Search')],
        ];
    }
}
