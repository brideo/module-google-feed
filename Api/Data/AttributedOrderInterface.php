<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api\Data;

/**
 * An order placed after a Google Ads click.
 *
 * @api
 */
interface AttributedOrderInterface
{
    /**
     * Order ID.
     *
     * @return int
     */
    public function getOrderId(): int;

    /**
     * Order number.
     *
     * @return string
     */
    public function getIncrementId(): string;

    /**
     * Store view ID.
     *
     * @return int
     */
    public function getStoreId(): int;

    /**
     * Order state.
     *
     * @return string
     */
    public function getState(): string;

    /**
     * Order status.
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Order creation time (UTC).
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Last order update time (UTC).
     *
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * Order currency.
     *
     * @return string
     */
    public function getOrderCurrencyCode(): string;

    /**
     * Base currency.
     *
     * @return string
     */
    public function getBaseCurrencyCode(): string;

    /**
     * Subtotal, order currency.
     *
     * @return float
     */
    public function getSubtotal(): float;

    /**
     * Shipping amount, order currency.
     *
     * @return float
     */
    public function getShippingAmount(): float;

    /**
     * Discount amount, order currency.
     *
     * @return float
     */
    public function getDiscountAmount(): float;

    /**
     * Grand total, order currency.
     *
     * @return float
     */
    public function getGrandTotal(): float;

    /**
     * Grand total, base currency.
     *
     * @return float
     */
    public function getBaseGrandTotal(): float;

    /**
     * Total refunded, order currency.
     *
     * @return float
     */
    public function getTotalRefunded(): float;

    /**
     * Google click ID.
     *
     * @return string|null
     */
    public function getGclid(): ?string;

    /**
     * iOS app-to-web click ID.
     *
     * @return string|null
     */
    public function getGbraid(): ?string;

    /**
     * iOS web-to-app click ID.
     *
     * @return string|null
     */
    public function getWbraid(): ?string;

    /**
     * SKU of the product page the click landed on.
     *
     * @return string|null
     */
    public function getLandingSku(): ?string;

    /**
     * Landing URL.
     *
     * @return string|null
     */
    public function getLandingUrl(): ?string;

    /**
     * Time of the click (UTC).
     *
     * @return string|null
     */
    public function getClickedAt(): ?string;

    /**
     * Purchased lines.
     *
     * @return \UpturnStudio\GoogleFeed\Api\Data\AttributedOrderItemInterface[]
     */
    public function getItems(): array;
}
