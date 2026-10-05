<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

use Magento\Catalog\Model\Product;

/**
 * Reads a product attribute as feed text, resolving option IDs to their store labels.
 */
class AttributeValueReader
{
    /**
     * Read an attribute value, empty string when the product has none.
     *
     * @param Product $product
     * @param string $attributeCode
     * @return string
     */
    public function read(Product $product, string $attributeCode): string
    {
        $value = $product->getData($attributeCode);
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        $attribute = $product->getResource()->getAttribute($attributeCode);
        if ($attribute && $attribute->usesSource()) {
            $text = $product->getAttributeText($attributeCode);
            if (is_array($text)) {
                $text = implode(', ', $text);
            }

            return $text === false || $text === null ? '' : trim((string) $text);
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
