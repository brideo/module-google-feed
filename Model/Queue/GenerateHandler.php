<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Queue;

use Psr\Log\LoggerInterface;
use UpturnStudio\GoogleFeed\Model\Feed\GenerationService;

/**
 * Consumer handler: generates queued feeds.
 *
 * Drained automatically by the core "consumers_runner" cron job, or on demand via
 * bin/magento queue:consumers:start upturnstudio_googlefeed.feed_generate.
 */
class GenerateHandler
{
    /**
     * @param GenerationService $generationService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly GenerationService $generationService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Generate a single feed.
     *
     * @param string $feedId
     * @return void
     */
    public function generate(string $feedId): void
    {
        try {
            $this->generationService->run((int) $feedId);
        } catch (\Throwable $e) {
            // The service has already recorded the failure on the feed; do not requeue.
            $this->logger->warning('UpturnStudio_GoogleFeed: queued generation stopped - ' . $e->getMessage());
        }
    }
}
