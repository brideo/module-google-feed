<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api\Data;

/**
 * A Google Ads click to attach to a cart.
 *
 * @api
 */
interface ClickInputInterface
{
    /**
     * Google click ID.
     *
     * @return string|null
     */
    public function getGclid(): ?string;

    /**
     * Set google click ID.
     *
     * @param string|null $gclid
     * @return $this
     */
    public function setGclid(?string $gclid): static;

    /**
     * iOS app-to-web click ID.
     *
     * @return string|null
     */
    public function getGbraid(): ?string;

    /**
     * Set iOS app-to-web click ID.
     *
     * @param string|null $gbraid
     * @return $this
     */
    public function setGbraid(?string $gbraid): static;

    /**
     * iOS web-to-app click ID.
     *
     * @return string|null
     */
    public function getWbraid(): ?string;

    /**
     * Set iOS web-to-app click ID.
     *
     * @param string|null $wbraid
     * @return $this
     */
    public function setWbraid(?string $wbraid): static;

    /**
     * SKU of the product page the click landed on.
     *
     * @return string|null
     */
    public function getLandingSku(): ?string;

    /**
     * Set sKU of the product page the click landed on.
     *
     * @param string|null $landingSku
     * @return $this
     */
    public function setLandingSku(?string $landingSku): static;

    /**
     * Landing URL.
     *
     * @return string|null
     */
    public function getLandingUrl(): ?string;

    /**
     * Set landing URL.
     *
     * @param string|null $landingUrl
     * @return $this
     */
    public function setLandingUrl(?string $landingUrl): static;

    /**
     * When the click landed, ISO 8601.
     *
     * @return string|null
     */
    public function getClickedAt(): ?string;

    /**
     * Set when the click landed, ISO 8601.
     *
     * @param string|null $clickedAt
     * @return $this
     */
    public function setClickedAt(?string $clickedAt): static;
}
