<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Resolver;

use Magento\Catalog\Model\Product;
use Magento\Framework\UrlInterface;
use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;

/**
 * Main image URL, taken from the parent when the variant has no image of its own.
 */
class Image implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string) __('Main Image URL');
    }

    /**
     * @inheritdoc
     */
    public function resolve(ProductContext $context): array
    {
        $file = $this->getFile($context->product) ?? ($context->parent ? $this->getFile($context->parent) : null);
        if ($file === null) {
            return [];
        }

        return [$this->toUrl($context, $file)];
    }

    /**
     * Media URL for a catalogue image file.
     *
     * @param ProductContext $context
     * @param string $file
     * @return string
     */
    public function toUrl(ProductContext $context, string $file): string
    {
        return $context->store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product/' . ltrim($file, '/');
    }

    /**
     * Base image file of a product, null when it has none.
     *
     * @param Product $product
     * @return string|null
     */
    public function getFile(Product $product): ?string
    {
        $file = (string) $product->getData('image');

        return $file === '' || $file === 'no_selection' ? null : $file;
    }
}
