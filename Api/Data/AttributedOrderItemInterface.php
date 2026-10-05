<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api\Data;

/**
 * A purchased line of a click-attributed order.
 *
 * @api
 */
interface AttributedOrderItemInterface
{
    /**
     * SKU bought; for a configurable product this is the chosen variant.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Product name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Product ID.
     *
     * @return int|null
     */
    public function getProductId(): ?int;

    /**
     * Product type.
     *
     * @return string
     */
    public function getProductType(): string;

    /**
     * Quantity ordered.
     *
     * @return float
     */
    public function getQtyOrdered(): float;

    /**
     * Quantity refunded.
     *
     * @return float
     */
    public function getQtyRefunded(): float;

    /**
     * Unit price excluding tax, order currency.
     *
     * @return float
     */
    public function getPrice(): float;

    /**
     * Row total excluding tax, order currency.
     *
     * @return float
     */
    public function getRowTotal(): float;

    /**
     * Row total including tax, order currency.
     *
     * @return float
     */
    public function getRowTotalInclTax(): float;

    /**
     * Discount on the row, order currency.
     *
     * @return float
     */
    public function getDiscountAmount(): float;

    /**
     * Tax on the row, order currency.
     *
     * @return float
     */
    public function getTaxAmount(): float;

    /**
     * Amount refunded for the row, order currency.
     *
     * @return float
     */
    public function getAmountRefunded(): float;

    /**
     * Row total excluding tax, base currency.
     *
     * @return float
     */
    public function getBaseRowTotal(): float;

    /**
     * Unit cost in base currency, when the product has one.
     *
     * @return float|null
     */
    public function getBaseCost(): ?float;
}
