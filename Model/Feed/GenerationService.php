<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use UpturnStudio\GoogleFeed\Model\Feed;
use UpturnStudio\GoogleFeed\Model\FeedRepository;
use UpturnStudio\GoogleFeed\Model\ResourceModel\Feed as FeedResource;

/**
 * Runs a feed generation and records its outcome on the feed.
 */
class GenerationService
{
    private const LOCK_PREFIX = 'upturnstudio_googlefeed_';

    /**
     * @param FeedRepository $feedRepository
     * @param FeedResource $feedResource
     * @param Generator $generator
     * @param LockManagerInterface $lockManager
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly FeedRepository $feedRepository,
        private readonly FeedResource $feedResource,
        private readonly Generator $generator,
        private readonly LockManagerInterface $lockManager,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Generate a feed now.
     *
     * @param int $feedId
     * @return int Number of items written
     * @throws LocalizedException When the feed does not exist, is already running, or generation fails
     */
    public function run(int $feedId): int
    {
        $feed = $this->feedRepository->getById($feedId);
        $lockName = self::LOCK_PREFIX . $feedId;
        if (!$this->lockManager->lock($lockName, 0)) {
            throw new LocalizedException(__('Feed "%1" is already being generated.', $feed->getName()));
        }

        try {
            $this->feedResource->updateRunState($feedId, ['last_status' => Feed::STATUS_RUNNING]);
            $count = $this->generator->generate($feed);
            $this->feedResource->updateRunState($feedId, [
                'last_status' => Feed::STATUS_SUCCESS,
                'last_message' => null,
                'product_count' => $count,
                'last_generated_at' => $this->dateTime->gmtDate(),
            ]);

            return $count;
        } catch (\Throwable $e) {
            $this->logger->error(
                'UpturnStudio_GoogleFeed: feed ' . $feedId . ' failed - ' . $e->getMessage(),
                ['exception' => $e]
            );
            $this->feedResource->updateRunState($feedId, [
                'last_status' => Feed::STATUS_ERROR,
                'last_message' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            throw new LocalizedException(__('Feed "%1" failed: %2', $feed->getName(), $e->getMessage()), $e);
        } finally {
            $this->lockManager->unlock($lockName);
        }
    }
}
