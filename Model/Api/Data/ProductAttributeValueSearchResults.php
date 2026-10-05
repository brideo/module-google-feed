<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api\Data;

use Magento\Framework\DataObject;
use UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueSearchResultsInterface;

/**
 * A page of stored Google attribute values.
 */
class ProductAttributeValueSearchResults extends DataObject implements ProductAttributeValueSearchResultsInterface
{
    /**
     * @inheritdoc
     */
    public function getItems(): array
    {
        return (array) ($this->getData('items') ?? []);
    }

    /**
     * @inheritdoc
     */
    public function getTotalCount(): int
    {
        return (int) $this->getData('total_count');
    }
}
