<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api\Data;

use Magento\Framework\DataObject;
use UpturnStudio\GoogleFeed\Api\Data\ProductAttributeValueInterface;

/**
 * A Google attribute value for one SKU, applied on top of the feed mapping.
 */
class ProductAttributeValue extends DataObject implements ProductAttributeValueInterface
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
    public function setSku(string $sku): static
    {
        return $this->setData('sku', $sku);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): ?int
    {
        return $this->getData('store_id') === null ? null : (int) $this->getData('store_id');
    }

    /**
     * @inheritdoc
     */
    public function setStoreId(?int $storeId): static
    {
        return $this->setData('store_id', $storeId);
    }

    /**
     * @inheritdoc
     */
    public function getAttributeCode(): string
    {
        return (string) $this->getData('attribute_code');
    }

    /**
     * @inheritdoc
     */
    public function setAttributeCode(string $attributeCode): static
    {
        return $this->setData('attribute_code', $attributeCode);
    }

    /**
     * @inheritdoc
     */
    public function getValue(): ?string
    {
        return $this->getData('value') === null ? null : (string) $this->getData('value');
    }

    /**
     * @inheritdoc
     */
    public function setValue(?string $value): static
    {
        return $this->setData('value', $value);
    }

    /**
     * @inheritdoc
     */
    public function getSource(): ?string
    {
        return $this->getData('source') === null ? null : (string) $this->getData('source');
    }

    /**
     * @inheritdoc
     */
    public function setSource(?string $source): static
    {
        return $this->setData('source', $source);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at') === null ? null : (string) $this->getData('updated_at');
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(?string $updatedAt): static
    {
        return $this->setData('updated_at', $updatedAt);
    }
}
