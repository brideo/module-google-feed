<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api;

use Magento\Framework\Exception\InputException;

/**
 * Parses the "updated from" filter of the list endpoints.
 */
class DateFilter
{
    /**
     * Convert an ISO 8601 or "Y-m-d H:i:s" time to the UTC format the database stores.
     *
     * A value without a timezone is read as UTC.
     *
     * @param string|null $value
     * @return string|null Null when no filter was given
     * @throws InputException
     */
    public static function normalise(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        try {
            $date = new \DateTimeImmutable(trim($value), new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            throw new InputException(__('"%1" is not a valid date and time.', $value));
        }

        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
