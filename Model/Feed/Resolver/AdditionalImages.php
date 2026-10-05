<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Resolver;

use Magento\Catalog\Model\Product;
use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;

/**
 * Gallery images other than the main one, up to Google's limit of ten.
 */
class AdditionalImages implements ResolverInterface
{
    private const LIMIT = 10;

    /**
     * @param Image $image
     */
    public function __construct(
        private readonly Image $image
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return (string) __('Additional Image URLs');
    }

    /**
     * @inheritdoc
     */
    public function resolve(ProductContext $context): array
    {
        $source = $this->image->getFile($context->product) !== null || $context->parent === null
            ? $context->product
            : $context->parent;
        $main = $this->image->getFile($source);

        $urls = [];
        foreach ($this->getGalleryFiles($source) as $file) {
            if ($file === $main) {
                continue;
            }
            $urls[] = $this->image->toUrl($context, $file);
            if (count($urls) === self::LIMIT) {
                break;
            }
        }

        return $urls;
    }

    /**
     * Enabled gallery image files in position order.
     *
     * @param Product $product
     * @return string[]
     */
    private function getGalleryFiles(Product $product): array
    {
        $gallery = $product->getData('media_gallery');
        $images = is_array($gallery) ? ($gallery['images'] ?? []) : [];
        usort($images, static fn (array $a, array $b): int => (int) ($a['position'] ?? 0) <=> (int) ($b['position'] ?? 0));

        $files = [];
        foreach ($images as $image) {
            $isImage = ($image['media_type'] ?? 'image') === 'image';
            if ($isImage && empty($image['disabled']) && !empty($image['file'])) {
                $files[] = (string) $image['file'];
            }
        }

        return array_values(array_unique($files));
    }
}
