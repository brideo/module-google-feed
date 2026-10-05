<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use UpturnStudio\GoogleFeed\Model\FeedRepository;

/**
 * Feed edit form.
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'UpturnStudio_GoogleFeed::feeds';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param FeedRepository $feedRepository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly FeedRepository $feedRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $feedId = (int) $this->getRequest()->getParam('feed_id');
        $title = __('New Feed');
        if ($feedId) {
            try {
                $title = $this->feedRepository->getById($feedId)->getName();
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This feed no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('UpturnStudio_GoogleFeed::feeds');
        $page->getConfig()->getTitle()->prepend(__('Google Feeds'));
        $page->getConfig()->getTitle()->prepend($title);

        return $page;
    }
}
