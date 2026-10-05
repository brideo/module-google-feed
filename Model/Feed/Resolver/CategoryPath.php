<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Resolver;

use UpturnStudio\GoogleFeed\Model\Feed\CategoryPathProvider;
use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;

/**
 * The product's deepest store category as a breadcrumb, e.g. "Men > Tops > Jackets".
 */
class CategoryPath implements ResolverInterface
{
    /**
     * @param CategoryPathProvider $categoryPathProvider
     */
    public function __construct(
        private readonly CategoryPathProvider $categoryPathProvider
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string) __('Category Path');
    }

    /**
     * @inheritdoc
     */
    public function resolve(ProductContext $context): array
    {
        $path = $this->categoryPathProvider->getDeepestPath($context->categoryIds, (int) $context->store->getId());

        return $path === '' ? [] : [$path];
    }
}
