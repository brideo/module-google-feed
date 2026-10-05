<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Resolver;

use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;

/**
 * A built-in mapping source that computes a value instead of reading one attribute.
 */
interface ResolverInterface
{
    /**
     * Label shown in the mapping editor.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Values for the item; an empty array when there is nothing to write.
     *
     * @param ProductContext $context
     * @return string[]
     */
    public function resolve(ProductContext $context): array;
}
