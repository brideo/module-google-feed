<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Resolver;

use UpturnStudio\GoogleFeed\Model\Config;
use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;

/**
 * Product weight with the store's weight unit.
 */
class Weight implements ResolverInterface
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string) __('Weight with Unit');
    }

    /**
     * @inheritdoc
     */
    public function resolve(ProductContext $context): array
    {
        $weight = (float) $context->product->getData('weight');
        if ($weight <= 0) {
            return [];
        }

        return [rtrim(rtrim(number_format($weight, 4, '.', ''), '0'), '.')
            . ' ' . $this->config->getWeightUnit($context->store->getId())];
    }
}
