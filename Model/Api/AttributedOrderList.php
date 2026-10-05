<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use UpturnStudio\GoogleFeed\Api\AttributedOrderListInterface;
use UpturnStudio\GoogleFeed\Api\Data\AttributedOrderInterface;
use UpturnStudio\GoogleFeed\Api\Data\AttributedOrderInterfaceFactory;
use UpturnStudio\GoogleFeed\Api\Data\AttributedOrderItemInterfaceFactory;
use UpturnStudio\GoogleFeed\Api\Data\AttributedOrderSearchResultsInterface;
use UpturnStudio\GoogleFeed\Api\Data\AttributedOrderSearchResultsInterfaceFactory;
use UpturnStudio\GoogleFeed\Model\ResourceModel\OrderClick;

/**
 * @inheritdoc
 */
class AttributedOrderList implements AttributedOrderListInterface
{
    private const CLICK_COLUMNS = ['gclid', 'gbraid', 'wbraid', 'landing_sku', 'landing_url', 'clicked_at'];

    /**
     * @param CollectionFactory $orderCollectionFactory
     * @param AttributedOrderInterfaceFactory $orderFactory
     * @param AttributedOrderItemInterfaceFactory $itemFactory
     * @param AttributedOrderSearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        private readonly CollectionFactory $orderCollectionFactory,
        private readonly AttributedOrderInterfaceFactory $orderFactory,
        private readonly AttributedOrderItemInterfaceFactory $itemFactory,
        private readonly AttributedOrderSearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getList(
        ?string $updatedFrom = null,
        int $pageSize = 100,
        int $currentPage = 1
    ): AttributedOrderSearchResultsInterface {
        $updatedFrom = DateFilter::normalise($updatedFrom);

        $collection = $this->orderCollectionFactory->create();
        $collection->getSelect()->join(
            ['click' => $collection->getTable(OrderClick::TABLE)],
            'click.order_id = main_table.entity_id',
            self::CLICK_COLUMNS
        );
        if ($updatedFrom !== null) {
            $collection->getSelect()->where('main_table.updated_at >= ?', $updatedFrom);
        }
        $collection->getSelect()->order(['main_table.updated_at ASC', 'main_table.entity_id ASC']);
        $collection->setPageSize(max(1, min($pageSize, self::MAX_PAGE_SIZE)))->setCurPage(max(1, $currentPage));

        $total = (int) $collection->getSize();
        $items = [];
        // Collections clamp an out-of-range page to the last one; an incremental sync needs an empty page instead.
        if ($collection->getCurPage() === max(1, $currentPage)) {
            foreach ($collection as $order) {
                $items[] = $this->buildOrder($order);
            }
        }

        return $this->searchResultsFactory->create(['data' => ['items' => $items, 'total_count' => $total]]);
    }

    /**
     * Map an order and its click to the API shape.
     *
     * @param Order $order
     * @return AttributedOrderInterface
     */
    private function buildOrder(Order $order): AttributedOrderInterface
    {
        $data = [
            'order_id' => (int) $order->getId(),
            'increment_id' => $order->getIncrementId(),
            'store_id' => (int) $order->getStoreId(),
            'state' => $order->getState(),
            'status' => $order->getStatus(),
            'created_at' => $order->getCreatedAt(),
            'updated_at' => $order->getUpdatedAt(),
            'order_currency_code' => $order->getOrderCurrencyCode(),
            'base_currency_code' => $order->getBaseCurrencyCode(),
            'subtotal' => $order->getSubtotal(),
            'shipping_amount' => $order->getShippingAmount(),
            'discount_amount' => abs((float) $order->getDiscountAmount()),
            'grand_total' => $order->getGrandTotal(),
            'base_grand_total' => $order->getBaseGrandTotal(),
            'total_refunded' => $order->getTotalRefunded(),
            'items' => $this->buildItems($order),
        ];
        foreach (self::CLICK_COLUMNS as $column) {
            $data[$column] = $order->getData($column);
        }

        return $this->orderFactory->create(['data' => $data]);
    }

    /**
     * One entry per purchased line; the hidden child rows of configurable and bundle items are folded in.
     *
     * @param Order $order
     * @return \UpturnStudio\GoogleFeed\Api\Data\AttributedOrderItemInterface[]
     */
    private function buildItems(Order $order): array
    {
        $childCosts = [];
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItemId() && $item->getBaseCost() !== null) {
                $childCosts[(int) $item->getParentItemId()] ??= 0.0;
                $childCosts[(int) $item->getParentItemId()] += (float) $item->getBaseCost();
            }
        }

        $items = [];
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItemId()) {
                continue;
            }
            $items[] = $this->itemFactory->create(['data' => [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'product_id' => $item->getProductId(),
                'product_type' => $item->getProductType(),
                'qty_ordered' => $item->getQtyOrdered(),
                'qty_refunded' => $item->getQtyRefunded(),
                'price' => $item->getPrice(),
                'row_total' => $item->getRowTotal(),
                'row_total_incl_tax' => $item->getRowTotalInclTax(),
                'discount_amount' => $item->getDiscountAmount(),
                'tax_amount' => $item->getTaxAmount(),
                'amount_refunded' => $item->getAmountRefunded(),
                'base_row_total' => $item->getBaseRowTotal(),
                'base_cost' => $item->getBaseCost() ?? ($childCosts[(int) $item->getItemId()] ?? null),
            ]]);
        }

        return $items;
    }
}
