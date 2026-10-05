<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api;

use Magento\Framework\Exception\InputException;
use Magento\Store\Model\StoreManagerInterface;
use UpturnStudio\GoogleFeed\Api\Data\BulkResultInterface;
use UpturnStudio\GoogleFeed\Api\Data\BulkResultInterfaceFactory;
use UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueInterface;
use UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueInterfaceFactory;
use UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueSearchResultsInterface;
use UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueSearchResultsInterfaceFactory;
use UpturnStudio\GoogleFeed\Api\ProductAttributeManagementInterface;
use UpturnStudio\GoogleFeed\Model\Feed\GoogleAttributePool;
use UpturnStudio\GoogleFeed\Model\ResourceModel\ProductAttribute as ProductAttributeResource;

/**
 * @inheritdoc
 */
class ProductAttributeManagement implements ProductAttributeManagementInterface
{
    private const DEFAULT_SOURCE = 'api';
    private const MAX_SKU_LENGTH = 64;
    private const MAX_VALUE_LENGTH = 10000;
    private const MAX_PAGE_SIZE = 1000;

    /**
     * @var bool[] Store ID => exists
     */
    private array $knownStores = [0 => true];

    /**
     * @param ProductAttributeResource $resource
     * @param GoogleAttributePool $attributePool
     * @param StoreManagerInterface $storeManager
     * @param BulkResultInterfaceFactory $bulkResultFactory
     * @param ProductAttributeValueInterfaceFactory $valueFactory
     * @param ProductAttributeValueSearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        private readonly ProductAttributeResource $resource,
        private readonly GoogleAttributePool $attributePool,
        private readonly StoreManagerInterface $storeManager,
        private readonly BulkResultInterfaceFactory $bulkResultFactory,
        private readonly ProductAttributeValueInterfaceFactory $valueFactory,
        private readonly ProductAttributeValueSearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(array $items): BulkResultInterface
    {
        $this->assertBatchSize($items);

        $rows = [];
        $errors = [];
        foreach ($items as $index => $item) {
            $error = $this->validate($item, true);
            if ($error !== null) {
                $errors[] = sprintf('Item %d: %s', $index, $error);
                continue;
            }
            // Keyed so a repeated SKU/store/code in one request keeps the last value instead of failing the insert.
            $key = mb_strtolower($item->getSku()) . '|' . (int) $item->getStoreId() . '|' . $item->getAttributeCode();
            $rows[$key] = [
                'sku' => $item->getSku(),
                'store_id' => (int) $item->getStoreId(),
                'attribute_code' => $item->getAttributeCode(),
                'value' => (string) $item->getValue(),
                'source' => mb_substr((string) ($item->getSource() ?: self::DEFAULT_SOURCE), 0, 32),
            ];
        }
        $this->resource->upsert(array_values($rows));

        return $this->bulkResultFactory->create(['data' => ['processed' => count($rows), 'errors' => $errors]]);
    }

    /**
     * @inheritdoc
     */
    public function delete(array $items): BulkResultInterface
    {
        $this->assertBatchSize($items);

        $removed = 0;
        $errors = [];
        foreach ($items as $index => $item) {
            $error = $this->validate($item, false);
            if ($error !== null) {
                $errors[] = sprintf('Item %d: %s', $index, $error);
                continue;
            }
            $removed += $this->resource->delete($item->getSku(), (int) $item->getStoreId(), $item->getAttributeCode());
        }

        return $this->bulkResultFactory->create(['data' => ['processed' => $removed, 'errors' => $errors]]);
    }

    /**
     * @inheritdoc
     */
    public function getList(
        ?string $sku = null,
        ?int $storeId = null,
        ?string $updatedFrom = null,
        int $pageSize = 200,
        int $currentPage = 1
    ): ProductAttributeValueSearchResultsInterface {
        $page = $this->resource->getPage(
            $sku,
            $storeId,
            DateFilter::normalise($updatedFrom),
            max(1, min($pageSize, self::MAX_PAGE_SIZE)),
            max(1, $currentPage)
        );

        $items = [];
        foreach ($page['rows'] as $row) {
            $items[] = $this->valueFactory->create(['data' => $row]);
        }

        return $this->searchResultsFactory->create(['data' => ['items' => $items, 'total_count' => $page['total']]]);
    }

    /**
     * Reject empty and oversized requests.
     *
     * @param array $items
     * @return void
     * @throws InputException
     */
    private function assertBatchSize(array $items): void
    {
        if (!$items) {
            throw new InputException(__('At least one item is required.'));
        }
        if (count($items) > self::MAX_ITEMS_PER_REQUEST) {
            throw new InputException(
                __('A request can carry at most %1 items; %2 were sent.', self::MAX_ITEMS_PER_REQUEST, count($items))
            );
        }
    }

    /**
     * Explain why an item cannot be stored, or return null when it is fine.
     *
     * @param ProductAttributeValueInterface $item
     * @param bool $withValue
     * @return string|null
     */
    private function validate(ProductAttributeValueInterface $item, bool $withValue): ?string
    {
        $sku = $item->getSku();
        if (trim($sku) === '' || mb_strlen($sku) > self::MAX_SKU_LENGTH) {
            return sprintf('sku is required and limited to %d characters.', self::MAX_SKU_LENGTH);
        }
        if (!$this->attributePool->isValidCode($item->getAttributeCode())) {
            return sprintf(
                'attribute_code "%s" must be lower-case letters, digits and underscores.',
                $item->getAttributeCode()
            );
        }
        if (!$this->storeExists((int) $item->getStoreId())) {
            return sprintf('store_id %d does not exist.', (int) $item->getStoreId());
        }
        if ($withValue && mb_strlen((string) $item->getValue()) > self::MAX_VALUE_LENGTH) {
            return sprintf('value is limited to %d characters.', self::MAX_VALUE_LENGTH);
        }

        return null;
    }

    /**
     * Whether the store view exists (0 means every store).
     *
     * @param int $storeId
     * @return bool
     */
    private function storeExists(int $storeId): bool
    {
        if (!isset($this->knownStores[$storeId])) {
            try {
                $this->storeManager->getStore($storeId);
                $this->knownStores[$storeId] = true;
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->knownStores[$storeId] = false;
            }
        }

        return $this->knownStores[$storeId];
    }
}
