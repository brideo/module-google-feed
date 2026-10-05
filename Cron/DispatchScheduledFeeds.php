<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Cron;

use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\FlagManager;
use Psr\Log\LoggerInterface;
use UpturnStudio\GoogleFeed\Model\FeedRepository;
use UpturnStudio\GoogleFeed\Model\Queue\Publisher;

/**
 * Queues every active feed whose own cron expression came due since the last check.
 *
 * Expressions are evaluated in the admin store timezone, like the rest of Magento's cron configuration.
 */
class DispatchScheduledFeeds
{
    private const FLAG_LAST_CHECK = 'upturnstudio_googlefeed_last_dispatch';

    /**
     * Minutes to look back after cron downtime, so one outage does not replay a whole day of schedules.
     */
    private const MAX_CATCH_UP_MINUTES = 60;

    /**
     * @param FeedRepository $feedRepository
     * @param Publisher $publisher
     * @param ScheduleFactory $scheduleFactory
     * @param FlagManager $flagManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly FeedRepository $feedRepository,
        private readonly Publisher $publisher,
        private readonly ScheduleFactory $scheduleFactory,
        private readonly FlagManager $flagManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Queue the feeds that are due.
     *
     * @return void
     */
    public function execute(): void
    {
        $now = intdiv(time(), 60) * 60;
        $lastCheck = (int) $this->flagManager->getFlagData(self::FLAG_LAST_CHECK);
        $from = max($lastCheck + 60, $now - self::MAX_CATCH_UP_MINUTES * 60);
        $this->flagManager->saveFlag(self::FLAG_LAST_CHECK, $now);

        foreach ($this->feedRepository->getAll(true) as $feed) {
            $expression = $feed->getCronExpression();
            if ($expression === '') {
                continue;
            }
            try {
                if ($this->isDue($expression, $from, $now)) {
                    $this->publisher->publishGenerate((int) $feed->getId());
                }
            } catch (\Throwable $e) {
                $this->logger->error(
                    'UpturnStudio_GoogleFeed: could not schedule feed ' . $feed->getId() . ' - ' . $e->getMessage()
                );
            }
        }
    }

    /**
     * Whether the expression matches any minute in the window.
     *
     * @param string $expression
     * @param int $from
     * @param int $to
     * @return bool
     */
    private function isDue(string $expression, int $from, int $to): bool
    {
        $schedule = $this->scheduleFactory->create();
        $schedule->setCronExpr($expression);
        for ($minute = $from; $minute <= $to; $minute += 60) {
            if ($schedule->setScheduledAt($minute)->trySchedule()) {
                return true;
            }
        }

        return false;
    }
}
