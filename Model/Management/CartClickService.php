<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Management;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use UpturnStudio\GoogleFeed\Model\Click\CookieParser;
use UpturnStudio\GoogleFeed\Model\Config;
use UpturnStudio\GoogleFeed\Model\ResourceModel\OrderClick;

/**
 * Attaches a Google Ads click to a cart, for headless storefronts that cannot send Magento the click cookie. When the
 * cart becomes an order, the click is recorded on it exactly as a cookie click would be.
 */
class CartClickService
{
    /**
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CartRepositoryInterface $cartRepository
     * @param CartManagementInterface $cartManagement
     * @param CookieParser $cookieParser
     * @param OrderClick $orderClick
     * @param Config $config
     */
    public function __construct(
        private readonly MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CartManagementInterface $cartManagement,
        private readonly CookieParser $cookieParser,
        private readonly OrderClick $orderClick,
        private readonly Config $config
    ) {
    }

    /**
     * Attach a click to a cart identified by its masked ID.
     *
     * A guest cannot reach a customer's cart: a cart that belongs to a customer is only found for that customer.
     *
     * @param string $maskedCartId
     * @param array $click With keys gclid, gbraid, wbraid, landing_sku, landing_url and clicked_at
     * @param int $customerId The signed-in customer, 0 for a guest
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function attachToMaskedCart(string $maskedCartId, array $click, int $customerId = 0): void
    {
        try {
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
            $quote = $this->cartRepository->get($quoteId);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(new Phrase('Could not find a cart with ID "%1".', [$maskedCartId]));
        }
        $owner = (int) $quote->getCustomerId();
        if (!$quote->getIsActive() || ($owner !== 0 && $owner !== $customerId)) {
            throw new NoSuchEntityException(new Phrase('Could not find a cart with ID "%1".', [$maskedCartId]));
        }

        $this->store((int) $quote->getId(), (int) $quote->getStoreId(), $click);
    }

    /**
     * Attach a click to a signed-in customer's active cart.
     *
     * @param int $customerId
     * @param array $click
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function attachToCustomerCart(int $customerId, array $click): void
    {
        $quote = $this->cartManagement->getCartForCustomer($customerId);

        $this->store((int) $quote->getId(), (int) $quote->getStoreId(), $click);
    }

    /**
     * @param int $quoteId
     * @param int $storeId
     * @param array $click
     * @return void
     * @throws InputException
     */
    private function store(int $quoteId, int $storeId, array $click): void
    {
        if (!$this->config->isTrackingEnabled($storeId)) {
            return;
        }
        $clickedAt = trim((string) ($click['clicked_at'] ?? ''));
        $timestamp = $clickedAt === '' ? false : strtotime($clickedAt);
        $normalized = $this->cookieParser->normalize([
            'gclid' => $click['gclid'] ?? null,
            'gbraid' => $click['gbraid'] ?? null,
            'wbraid' => $click['wbraid'] ?? null,
            'sku' => $click['landing_sku'] ?? null,
            'url' => $click['landing_url'] ?? null,
            'ts' => $timestamp === false ? null : $timestamp * 1000,
        ]);
        if ($normalized === null) {
            throw new InputException(new Phrase('A valid gclid, gbraid or wbraid is required.'));
        }

        $this->orderClick->saveQuoteClick($quoteId, $normalized);
    }
}
