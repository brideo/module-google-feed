<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use UpturnStudio\GoogleFeed\Model\Management\ExceptionMessages;
use UpturnStudio\GoogleFeed\Model\Management\FeedService;

/**
 * Saves a feed definition. The checks are the same ones the API applies, because both go through FeedService.
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'UpturnStudio_GoogleFeed::feeds';

    /**
     * @param Context $context
     * @param FeedService $feedService
     */
    public function __construct(
        Context $context,
        private readonly FeedService $feedService
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $data = (array) $this->getRequest()->getPostValue();
        $feedId = (int) ($data['feed_id'] ?? 0);

        try {
            // The form posts nothing for a cleared multiselect or a deleted last mapping row, so every field is
            // passed explicitly: an absent field would otherwise mean "leave unchanged".
            $input = [
                'name' => $data['name'] ?? '',
                'store_id' => $data['store_id'] ?? 0,
                'is_active' => $data['is_active'] ?? false,
                'target_country' => $data['target_country'] ?? '',
                'price_tax' => $data['price_tax'] ?? 'auto',
                'cron_expression' => $data['cron_expression'] ?? '',
                'filters' => [
                    'category_ids' => $data['category_ids'] ?? [],
                    'product_types' => $data['product_types'] ?? [],
                    'visibility' => $data['visibility'] ?? [],
                    'attribute_set_ids' => $data['attribute_set_ids'] ?? [],
                    'exclude_out_of_stock' => $data['exclude_out_of_stock'] ?? false,
                ],
                'mapping' => $data['mapping'] ?? [],
            ];
            $feed = $feedId ? $this->feedService->update($feedId, $input) : $this->feedService->create($input);
            $this->messageManager->addSuccessMessage(__('The feed has been saved.'));

            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['feed_id' => $feed['feed_id']]);
            }

            return $redirect->setPath('*/*/');
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(ExceptionMessages::collect($e));

            return $feedId
                ? $redirect->setPath('*/*/edit', ['feed_id' => $feedId])
                : $redirect->setPath('*/*/new');
        }
    }
}
