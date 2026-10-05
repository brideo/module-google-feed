<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api\Data;

/**
 * A Google attribute value for one SKU, applied on top of the feed mapping.
 *
 * @api
 */
interface ProductAttributeValueInterface
{
    /**
     * Product SKU.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Set product SKU.
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): static;

    /**
     * Store view ID; 0 or omitted applies to every store.
     *
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * Set store view ID; 0 or omitted applies to every store.
     *
     * @param int|null $storeId
     * @return $this
     */
    public function setStoreId(?int $storeId): static;

    /**
     * Google attribute code, e.g. google_product_category.
     *
     * @return string
     */
    public function getAttributeCode(): string;

    /**
     * Set google attribute code, e.g. google_product_category.
     *
     * @param string $attributeCode
     * @return $this
     */
    public function setAttributeCode(string $attributeCode): static;

    /**
     * Value; empty removes the attribute from the item.
     *
     * @return string|null
     */
    public function getValue(): ?string;

    /**
     * Set value; empty removes the attribute from the item.
     *
     * @param string|null $value
     * @return $this
     */
    public function setValue(?string $value): static;

    /**
     * Label for whoever wrote the value.
     *
     * @return string|null
     */
    public function getSource(): ?string;

    /**
     * Set label for whoever wrote the value.
     *
     * @param string|null $source
     * @return $this
     */
    public function setSource(?string $source): static;

    /**
     * Last update time (UTC).
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set last update time (UTC).
     *
     * @param string|null $updatedAt
     * @return $this
     */
    public function setUpdatedAt(?string $updatedAt): static;
}
