<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Resolver;

use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;

/**
 * Parent SKU, which groups the variants of one configurable product.
 */
class ItemGroupId implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string) __('Parent SKU (variants only)');
    }

    /**
     * @inheritdoc
     */
    public function resolve(ProductContext $context): array
    {
        return $context->parent === null ? [] : [(string) $context->parent->getSku()];
    }
}
