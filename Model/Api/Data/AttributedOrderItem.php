<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api\Data;

use Magento\Framework\DataObject;
use UpturnStudio\GoogleFeed\Api\Data\AttributedOrderItemInterface;

/**
 * A purchased line of a click-attributed order.
 */
class AttributedOrderItem extends DataObject implements AttributedOrderItemInterface
{
    /**
     * @inheritdoc
     */
    public function getSku(): string
    {
        return (string) $this->getData('sku');
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return (string) $this->getData('name');
    }

    /**
     * @inheritdoc
     */
    public function getProductId(): ?int
    {
        return $this->getData('product_id') === null ? null : (int) $this->getData('product_id');
    }

    /**
     * @inheritdoc
     */
    public function getProductType(): string
    {
        return (string) $this->getData('product_type');
    }

    /**
     * @inheritdoc
     */
    public function getQtyOrdered(): float
    {
        return (float) $this->getData('qty_ordered');
    }

    /**
     * @inheritdoc
     */
    public function getQtyRefunded(): float
    {
        return (float) $this->getData('qty_refunded');
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): float
    {
        return (float) $this->getData('price');
    }

    /**
     * @inheritdoc
     */
    public function getRowTotal(): float
    {
        return (float) $this->getData('row_total');
    }

    /**
     * @inheritdoc
     */
    public function getRowTotalInclTax(): float
    {
        return (float) $this->getData('row_total_incl_tax');
    }

    /**
     * @inheritdoc
     */
    public function getDiscountAmount(): float
    {
        return (float) $this->getData('discount_amount');
    }

    /**
     * @inheritdoc
     */
    public function getTaxAmount(): float
    {
        return (float) $this->getData('tax_amount');
    }

    /**
     * @inheritdoc
     */
    public function getAmountRefunded(): float
    {
        return (float) $this->getData('amount_refunded');
    }

    /**
     * @inheritdoc
     */
    public function getBaseRowTotal(): float
    {
        return (float) $this->getData('base_row_total');
    }

    /**
     * @inheritdoc
     */
    public function getBaseCost(): ?float
    {
        return $this->getData('base_cost') === null ? null : (float) $this->getData('base_cost');
    }
}
