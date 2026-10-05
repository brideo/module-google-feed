<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Queue;

use Magento\Framework\MessageQueue\PublisherInterface;
use UpturnStudio\GoogleFeed\Model\Feed;
use UpturnStudio\GoogleFeed\Model\ResourceModel\Feed as FeedResource;

/**
 * Enqueues feed generation onto the MySQL-backed message queue.
 */
class Publisher
{
    public const TOPIC_FEED_GENERATE = 'upturnstudio_googlefeed.feed_generate';

    /**
     * @param PublisherInterface $publisher
     * @param FeedResource $feedResource
     */
    public function __construct(
        private readonly PublisherInterface $publisher,
        private readonly FeedResource $feedResource
    ) {
    }

    /**
     * Queue a generation run and mark the feed as queued.
     *
     * @param int $feedId
     * @return void
     */
    public function publishGenerate(int $feedId): void
    {
        $this->publisher->publish(self::TOPIC_FEED_GENERATE, (string) $feedId);
        $this->feedResource->updateRunState($feedId, ['last_status' => Feed::STATUS_QUEUED]);
    }
}
