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

/**
 * Deletes a feed and its generated file.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'UpturnStudio_GoogleFeed::feeds';

    /**
     * @param Context $context
     * @param FeedRepository $feedRepository
     */
    public function __construct(
        Context $context,
        private readonly FeedRepository $feedRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        try {
            $this->feedRepository->delete(
                $this->feedRepository->getById((int) $this->getRequest()->getParam('feed_id'))
            );
            $this->messageManager->addSuccessMessage(__('The feed has been deleted.'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $redirect;
    }
}
