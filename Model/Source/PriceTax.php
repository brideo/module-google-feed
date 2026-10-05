<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use UpturnStudio\GoogleFeed\Model\Feed;

/**
 * How prices are listed with respect to tax.
 */
class PriceTax implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Feed::PRICE_TAX_AUTO, 'label' => __('As displayed in the store')],
            ['value' => Feed::PRICE_TAX_INCLUDING, 'label' => __('Including tax')],
            ['value' => Feed::PRICE_TAX_EXCLUDING, 'label' => __('Excluding tax')],
        ];
    }
}
