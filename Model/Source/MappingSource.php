<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use UpturnStudio\GoogleFeed\Model\Feed\Resolver\ResolverPool;
use UpturnStudio\GoogleFeed\Model\Feed\ValueResolver;

/**
 * Where a mapped Google attribute takes its value from.
 */
class MappingSource implements OptionSourceInterface
{
    /**
     * @param ResolverPool $resolverPool
     * @param CollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        private readonly ResolverPool $resolverPool,
        private readonly CollectionFactory $attributeCollectionFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $builtIn = [];
        foreach ($this->resolverPool->getAll() as $code => $resolver) {
            $builtIn[] = ['value' => ValueResolver::SOURCE_RESOLVER . ':' . $code, 'label' => $resolver->getLabel()];
        }

        $attributes = [];
        $collection = $this->attributeCollectionFactory->create();
        $collection->addVisibleFilter()->setOrder('frontend_label', 'ASC');
        foreach ($collection as $attribute) {
            $code = (string) $attribute->getAttributeCode();
            $attributes[] = [
                'value' => ValueResolver::SOURCE_ATTRIBUTE . ':' . $code,
                'label' => ($attribute->getFrontendLabel() ?: $code) . ' [' . $code . ']',
            ];
        }

        return [
            ['value' => ValueResolver::SOURCE_STATIC, 'label' => __('Static value')],
            ['value' => ValueResolver::SOURCE_TEMPLATE, 'label' => __('Template')],
            ['label' => __('Built-in'), 'value' => $builtIn],
            ['label' => __('Product attributes'), 'value' => $attributes],
        ];
    }
}
