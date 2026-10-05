<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Management;

use Magento\Framework\Exception\AbstractAggregateException;

/**
 * Flattens an exception, and any validation errors it carries, into one readable message.
 */
class ExceptionMessages
{
    /**
     * @param \Throwable $e
     * @return string
     */
    public static function collect(\Throwable $e): string
    {
        // With a single error Magento puts its text in the message and leaves the error list empty.
        if ($e instanceof AbstractAggregateException && $e->getErrors()) {
            $messages = [];
            foreach ($e->getErrors() as $error) {
                $messages[] = $error->getMessage();
            }

            return implode(' ', $messages);
        }

        return $e->getMessage();
    }
}
