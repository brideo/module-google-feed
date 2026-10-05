<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api\Data;

use Magento\Framework\DataObject;
use UpturnStudio\GoogleFeed\Api\Data\BulkResultInterface;

/**
 * Outcome of a bulk write.
 */
class BulkResult extends DataObject implements BulkResultInterface
{
    /**
     * @inheritdoc
     */
    public function getProcessed(): int
    {
        return (int) $this->getData('processed');
    }

    /**
     * @inheritdoc
     */
    public function getErrors(): array
    {
        return (array) ($this->getData('errors') ?? []);
    }
}
