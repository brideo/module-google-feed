<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Resolver;

use UpturnStudio\GoogleFeed\Model\Feed\PriceCalculator;
use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;

/**
 * Final price, written only when a special price or catalogue rule puts it below the regular price.
 */
class SalePrice implements ResolverInterface
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
        return (string) __('Sale Price');
    }

    /**
     * @inheritdoc
     */
    public function resolve(ProductContext $context): array
    {
        $prices = $this->priceCalculator->getPrices($context);
        if ($prices['final'] <= 0 || $prices['final'] >= $prices['regular']) {
            return [];
        }

        return [$this->priceCalculator->format($prices['final'], (string) $context->store->getCurrentCurrencyCode())];
    }
}
