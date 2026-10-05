<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api;

use UpturnStudio\GoogleFeed\Api\CartClickInterface;
use UpturnStudio\GoogleFeed\Api\Data\ClickInputInterface;
use UpturnStudio\GoogleFeed\Model\Management\CartClickService;

/**
 * @inheritdoc
 */
class CartClick implements CartClickInterface
{
    /**
     * @param CartClickService $cartClickService
     * @param DataConverter $converter
     */
    public function __construct(
        private readonly CartClickService $cartClickService,
        private readonly DataConverter $converter
    ) {
    }

    /**
     * @inheritdoc
     */
    public function saveForGuestCart(string $cartId, ClickInputInterface $click): bool
    {
        $this->cartClickService->attachToMaskedCart($cartId, $this->converter->toArray($click));

        return true;
    }

    /**
     * @inheritdoc
     */
    public function saveForCustomerCart(int $customerId, ClickInputInterface $click): bool
    {
        $this->cartClickService->attachToCustomerCart($customerId, $this->converter->toArray($click));

        return true;
    }
}
