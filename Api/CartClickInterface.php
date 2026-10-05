<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api;

use UpturnStudio\GoogleFeed\Api\Data\ClickInputInterface;

/**
 * Attaches a Google Ads click to a cart, for headless storefronts that cannot send Magento the click cookie.
 *
 * @api
 */
interface CartClickInterface
{
    /**
     * Attach a click to a guest cart.
     *
     * @param string $cartId Masked cart ID
     * @param \UpturnStudio\GoogleFeed\Api\Data\ClickInputInterface $click
     * @return bool
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveForGuestCart(string $cartId, ClickInputInterface $click): bool;

    /**
     * Attach a click to the signed-in customer's cart.
     *
     * @param int $customerId
     * @param \UpturnStudio\GoogleFeed\Api\Data\ClickInputInterface $click
     * @return bool
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveForCustomerCart(int $customerId, ClickInputInterface $click): bool;
}
