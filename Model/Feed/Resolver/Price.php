<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Resolver;

use UpturnStudio\GoogleFeed\Model\Feed\PriceCalculator;
use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;

/**
 * Regular (non-sale) price with currency.
 */
class Price implements ResolverInterface
{
    /**
     * @param PriceCalculator $priceCalculator
     */
    public function __construct(
        private readonly PriceCalculator $priceCalculator
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string) __('Price');
    }

    /**
     * @inheritdoc
     */
    public function resolve(ProductContext $context): array
    {
        $prices = $this->priceCalculator->getPrices($context);
        // A product with only a final price (e.g. tier-priced at zero base) still needs a price.
        $amount = $prices['regular'] > 0 ? $prices['regular'] : $prices['final'];
        if ($amount <= 0) {
            return [];
        }

        return [$this->priceCalculator->format($amount, (string) $context->store->getCurrentCurrencyCode())];
    }
}
