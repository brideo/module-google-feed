<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api\Data;

use Magento\Framework\DataObject;
use UpturnStudio\GoogleFeed\Api\Data\AttributedOrderInterface;

/**
 * An order placed after a Google Ads click.
 */
class AttributedOrder extends DataObject implements AttributedOrderInterface
{
    /**
     * @inheritdoc
     */
    public function getOrderId(): int
    {
        return (int) $this->getData('order_id');
    }

    /**
     * @inheritdoc
     */
    public function getIncrementId(): string
    {
        return (string) $this->getData('increment_id');
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): int
    {
        return (int) $this->getData('store_id');
    }

    /**
     * @inheritdoc
     */
    public function getState(): string
    {
        return (string) $this->getData('state');
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): string
    {
        return (string) $this->getData('status');
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): string
    {
        return (string) $this->getData('created_at');
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): string
    {
        return (string) $this->getData('updated_at');
    }

    /**
     * @inheritdoc
     */
    public function getOrderCurrencyCode(): string
    {
        return (string) $this->getData('order_currency_code');
    }

    /**
     * @inheritdoc
     */
    public function getBaseCurrencyCode(): string
    {
        return (string) $this->getData('base_currency_code');
    }

    /**
     * @inheritdoc
     */
    public function getSubtotal(): float
    {
        return (float) $this->getData('subtotal');
    }

    /**
     * @inheritdoc
     */
    public function getShippingAmount(): float
    {
        return (float) $this->getData('shipping_amount');
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
    public function getGrandTotal(): float
    {
        return (float) $this->getData('grand_total');
    }

    /**
     * @inheritdoc
     */
    public function getBaseGrandTotal(): float
    {
        return (float) $this->getData('base_grand_total');
    }

    /**
     * @inheritdoc
     */
    public function getTotalRefunded(): float
    {
        return (float) $this->getData('total_refunded');
    }

    /**
     * @inheritdoc
     */
    public function getGclid(): ?string
    {
        return $this->getData('gclid') === null ? null : (string) $this->getData('gclid');
    }

    /**
     * @inheritdoc
     */
    public function getGbraid(): ?string
    {
        return $this->getData('gbraid') === null ? null : (string) $this->getData('gbraid');
    }

    /**
     * @inheritdoc
     */
    public function getWbraid(): ?string
    {
        return $this->getData('wbraid') === null ? null : (string) $this->getData('wbraid');
    }

    /**
     * @inheritdoc
     */
    public function getLandingSku(): ?string
    {
        return $this->getData('landing_sku') === null ? null : (string) $this->getData('landing_sku');
    }

    /**
     * @inheritdoc
     */
    public function getLandingUrl(): ?string
    {
        return $this->getData('landing_url') === null ? null : (string) $this->getData('landing_url');
    }

    /**
     * @inheritdoc
     */
    public function getClickedAt(): ?string
    {
        return $this->getData('clicked_at') === null ? null : (string) $this->getData('clicked_at');
    }

    /**
     * @inheritdoc
     */
    public function getItems(): array
    {
        return (array) ($this->getData('items') ?? []);
    }
}
