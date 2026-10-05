<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api\Data;

/**
 * Outcome of a bulk write.
 *
 * @api
 */
interface BulkResultInterface
{
    /**
     * Number of items written or removed.
     *
     * @return int
     */
    public function getProcessed(): int;

    /**
     * One message per rejected item.
     *
     * @return string[]
     */
    public function getErrors(): array;
}
