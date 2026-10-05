<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Controller\Adminhtml\Feed;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\Exception\LocalizedException;
use UpturnStudio\GoogleFeed\Model\Feed\GoogleAttributePool;
use UpturnStudio\GoogleFeed\Model\FeedFactory;
use UpturnStudio\GoogleFeed\Model\FeedRepository;

/**
 * Saves a feed definition.
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'UpturnStudio_GoogleFeed::feeds';

    /**
     * @param Context $context
     * @param FeedRepository $feedRepository
     * @param FeedFactory $feedFactory
     * @param GoogleAttributePool $attributePool
     * @param ScheduleFactory $scheduleFactory
     */
    public function __construct(
        Context $context,
        private readonly FeedRepository $feedRepository,
        private readonly FeedFactory $feedFactory,
        private readonly GoogleAttributePool $attributePool,
        private readonly ScheduleFactory $scheduleFactory
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
            $feed = $feedId ? $this->feedRepository->getById($feedId) : $this->feedFactory->create();
            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                throw new LocalizedException(__('A feed name is required.'));
            }
            $cronExpression = trim((string) ($data['cron_expression'] ?? ''));
            $this->assertValidCronExpression($cronExpression);

            $feed->addData([
                'name' => $name,
                'store_id' => (int) ($data['store_id'] ?? 0),
                'is_active' => empty($data['is_active']) ? 0 : 1,
                'target_country' => strtoupper(substr((string) ($data['target_country'] ?? ''), 0, 2)) ?: null,
                'price_tax' => (string) ($data['price_tax'] ?? 'auto'),
                'cron_expression' => $cronExpression ?: null,
                'filters' => json_encode([
                    'category_ids' => $this->toIntList($data['category_ids'] ?? []),
                    'product_types' => array_values(array_filter((array) ($data['product_types'] ?? []))),
                    'visibility' => $this->toIntList($data['visibility'] ?? []),
                    'attribute_set_ids' => $this->toIntList($data['attribute_set_ids'] ?? []),
                    'exclude_out_of_stock' => !empty($data['exclude_out_of_stock']),
                ]),
                'mapping' => json_encode($this->cleanMapping((array) ($data['mapping'] ?? []))),
            ]);
            $this->feedRepository->save($feed);
            $this->messageManager->addSuccessMessage(__('The feed has been saved.'));

            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['feed_id' => $feed->getId()]);
            }

            return $redirect->setPath('*/*/');
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $feedId
                ? $redirect->setPath('*/*/edit', ['feed_id' => $feedId])
                : $redirect->setPath('*/*/new');
        }
    }

    /**
     * Keep one well-formed row per Google attribute; a later row for the same attribute replaces an earlier one.
     *
     * @param array $rows
     * @return array[]
     */
    private function cleanMapping(array $rows): array
    {
        $mapping = [];
        foreach ($rows as $row) {
            $code = is_array($row) ? trim((string) ($row['google_attribute'] ?? '')) : '';
            $source = is_array($row) ? trim((string) ($row['source'] ?? '')) : '';
            if ($source === '' || !$this->attributePool->isValidCode($code)) {
                continue;
            }
            $mapping[$code] = [
                'google_attribute' => $code,
                'source' => $source,
                'value' => (string) ($row['value'] ?? ''),
                'fallback' => (string) ($row['fallback'] ?? ''),
                'use_parent' => empty($row['use_parent']) ? '0' : '1',
            ];
        }

        return array_values($mapping);
    }

    /**
     * Normalise a posted multiselect to a list of positive integers.
     *
     * @param mixed $values
     * @return int[]
     */
    private function toIntList(mixed $values): array
    {
        return array_values(array_filter(array_map('intval', (array) $values)));
    }

    /**
     * Reject a schedule the cron dispatcher would not be able to evaluate.
     *
     * @param string $expression
     * @return void
     * @throws LocalizedException
     */
    private function assertValidCronExpression(string $expression): void
    {
        if ($expression === '') {
            return;
        }
        try {
            $this->scheduleFactory->create()->setCronExpr($expression)->setScheduledAt(time())->trySchedule();
        } catch (\Throwable $e) {
            throw new LocalizedException(__('"%1" is not a valid cron expression.', $expression));
        }
    }
}
