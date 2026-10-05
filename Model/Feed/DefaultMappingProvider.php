<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;

/**
 * Starting mapping for a new feed, taken from the defaults declared with each Google attribute.
 */
class DefaultMappingProvider
{
    /**
     * @param GoogleAttributePool $attributePool
     * @param EavConfig $eavConfig
     */
    public function __construct(
        private readonly GoogleAttributePool $attributePool,
        private readonly EavConfig $eavConfig
    ) {
    }

    /**
     * Mapping rows for every Google attribute with a usable default source.
     *
     * @return array[]
     */
    public function get(): array
    {
        $rows = [];
        foreach ($this->attributePool->getAll() as $code => $definition) {
            $source = (string) ($definition['default_source'] ?? '');
            if ($source === '' || !$this->isUsable($source)) {
                continue;
            }
            $rows[] = [
                'google_attribute' => $code,
                'source' => $source,
                'value' => (string) ($definition['default_value'] ?? ''),
                'fallback' => '',
                'use_parent' => empty($definition['default_use_parent']) ? '0' : '1',
            ];
        }

        return $rows;
    }

    /**
     * Attribute sources only apply when the store actually has that product attribute.
     *
     * @param string $source
     * @return bool
     */
    private function isUsable(string $source): bool
    {
        $prefix = ValueResolver::SOURCE_ATTRIBUTE . ':';
        if (!str_starts_with($source, $prefix)) {
            return true;
        }

        return (bool) $this->eavConfig->getAttribute(Product::ENTITY, substr($source, strlen($prefix)))->getId();
    }
}
