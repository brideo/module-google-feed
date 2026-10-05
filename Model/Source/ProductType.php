<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Api\ProductTypeListInterface;

/**
 * Product types a feed can list. Configurable products are always listed through their variants.
 */
class ProductType implements OptionSourceInterface
{
    private const UNSUPPORTED = ['configurable'];

    /**
     * @param ProductTypeListInterface $productTypeList
     */
    public function __construct(
        private readonly ProductTypeListInterface $productTypeList
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->productTypeList->getProductTypes() as $type) {
            if (!in_array($type->getName(), self::UNSUPPORTED, true)) {
                $options[] = ['value' => $type->getName(), 'label' => $type->getLabel()];
            }
        }

        return $options;
    }
}
