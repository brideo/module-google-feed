<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use UpturnStudio\GoogleFeed\Model\FeedFactory;
use UpturnStudio\GoogleFeed\Model\FeedRepository;

/**
 * Copies a feed definition into a new, inactive feed.
 */
class Duplicate extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'UpturnStudio_GoogleFeed::feeds';

    /**
     * @param Context $context
     * @param FeedRepository $feedRepository
     * @param FeedFactory $feedFactory
     */
    public function __construct(
        Context $context,
        private readonly FeedRepository $feedRepository,
        private readonly FeedFactory $feedFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        try {
            $source = $this->feedRepository->getById((int) $this->getRequest()->getParam('feed_id'));
            $copy = $this->feedFactory->create();
            foreach (['store_id', 'target_country', 'price_tax', 'filters', 'mapping', 'cron_expression'] as $field) {
                $copy->setData($field, $source->getData($field));
            }
            $copy->setData('name', __('%1 (copy)', $source->getName())->render());
            $copy->setData('is_active', 0);
            $this->feedRepository->save($copy);
            $this->messageManager->addSuccessMessage(__('The feed has been duplicated. Review it, then enable it.'));

            return $redirect->setPath('*/*/edit', ['feed_id' => $copy->getId()]);
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $redirect->setPath('*/*/');
        }
    }
}
