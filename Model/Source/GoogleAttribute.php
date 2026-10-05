<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use UpturnStudio\GoogleFeed\Model\Feed\GoogleAttributePool;

/**
 * Google attributes that can be mapped.
 */
class GoogleAttribute implements OptionSourceInterface
{
    /**
     * @param GoogleAttributePool $attributePool
     */
    public function __construct(
        private readonly GoogleAttributePool $attributePool
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->attributePool->getAll() as $code => $definition) {
            $options[] = ['value' => $code, 'label' => ($definition['label'] ?? $code) . ' [' . $code . ']'];
        }

        return $options;
    }
}
