<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use UpturnStudio\GoogleFeed\Model\FeedRepository;
use UpturnStudio\GoogleFeed\Model\Queue\Publisher;

/**
 * Queues a feed for generation.
 */
class Generate extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'UpturnStudio_GoogleFeed::feeds';

    /**
     * @param Context $context
     * @param FeedRepository $feedRepository
     * @param Publisher $publisher
     */
    public function __construct(
        Context $context,
        private readonly FeedRepository $feedRepository,
        private readonly Publisher $publisher
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $feed = $this->feedRepository->getById((int) $this->getRequest()->getParam('feed_id'));
            $this->publisher->publishGenerate((int) $feed->getId());
            $this->messageManager->addSuccessMessage(
                __('Feed "%1" has been queued and will be generated on the next cron run.', $feed->getName())
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setRefererOrBaseUrl();
    }
}
