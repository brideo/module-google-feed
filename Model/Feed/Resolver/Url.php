<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Resolver;

use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;

/**
 * Storefront URL; variants without their own page link to the parent.
 */
class Url implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string) __('Product URL');
    }

    /**
     * @inheritdoc
     */
    public function resolve(ProductContext $context): array
    {
        $url = (string) $context->getPageProduct()->getProductUrl();

        return $url === '' ? [] : [$url];
    }
}
