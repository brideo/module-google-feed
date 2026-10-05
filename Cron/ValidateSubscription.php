<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Cron;

use UpturnStudio\GoogleFeed\Model\Licence\Status;

/**
 * Daily re-check of the subscription key.
 */
class ValidateSubscription
{
    /**
     * @param Status $status
     */
    public function __construct(
        private readonly Status $status
    ) {
    }

    /**
     * Refresh the stored subscription state.
     *
     * @return void
     */
    public function execute(): void
    {
        $this->status->refresh();
    }
}
