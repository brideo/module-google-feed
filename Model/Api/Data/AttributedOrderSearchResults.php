<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api\Data;

use Magento\Framework\DataObject;
use UpturnStudio\GoogleFeed\Api\Data\AttributedOrderSearchResultsInterface;

/**
 * A page of click-attributed orders.
 */
class AttributedOrderSearchResults extends DataObject implements AttributedOrderSearchResultsInterface
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
