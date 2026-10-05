<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api\Data;

use Magento\Framework\DataObject;
use UpturnStudio\GoogleFeed\Api\Data\ClickInputInterface;

/**
 * A Google Ads click to attach to a cart.
 */
class ClickInput extends DataObject implements ClickInputInterface
{
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
    public function setGclid(?string $gclid): static
    {
        return $this->setData('gclid', $gclid);
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
    public function setGbraid(?string $gbraid): static
    {
        return $this->setData('gbraid', $gbraid);
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
    public function setWbraid(?string $wbraid): static
    {
        return $this->setData('wbraid', $wbraid);
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
    public function setLandingSku(?string $landingSku): static
    {
        return $this->setData('landing_sku', $landingSku);
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
    public function setLandingUrl(?string $landingUrl): static
    {
        return $this->setData('landing_url', $landingUrl);
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
    public function setClickedAt(?string $clickedAt): static
    {
        return $this->setData('clicked_at', $clickedAt);
    }
}
