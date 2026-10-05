<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api;

use Magento\Framework\DataObject;
use UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueInterfaceFactory;

/**
 * Converts between the plain arrays the services use and the data objects the REST layer serialises.
 */
class DataConverter
{
    /**
     * @param ProductAttributeValueInterfaceFactory $attributeValueFactory
     */
    public function __construct(
        private readonly ProductAttributeValueInterfaceFactory $attributeValueFactory
    ) {
    }

    /**
     * Turn a data object, or a list of them, into plain arrays holding only the fields that were set.
     *
     * @param mixed $value
     * @return mixed
     */
    public function toArray(mixed $value): mixed
    {
        if ($value instanceof DataObject) {
            return array_map([$this, 'toArray'], $value->getData());
        }
        if (is_array($value)) {
            return array_map([$this, 'toArray'], $value);
        }

        return $value;
    }

    /**
     * @param array[] $items Attribute value arrays with keys sku, store_id, attribute_code, value and source
     * @return \UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueInterface[]
     */
    public function attributeValues(array $items): array
    {
        return array_map(
            fn (array $item) => $this->attributeValueFactory->create(['data' => $item]),
            array_values(array_filter($items, 'is_array'))
        );
    }
}
