<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Resolver;

use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;

/**
 * Availability from the salable quantity of the store's stock.
 */
class Availability implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string) __('Availability');
    }

    /**
     * @inheritdoc
     */
    public function resolve(ProductContext $context): array
    {
        return [$context->salable ? 'in_stock' : 'out_of_stock'];
    }
}
