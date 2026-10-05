<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Observer;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Psr\Log\LoggerInterface;
use UpturnStudio\GoogleFeed\Model\Click\CookieParser;
use UpturnStudio\GoogleFeed\Model\Config;
use UpturnStudio\GoogleFeed\Model\ResourceModel\OrderClick;

/**
 * Copies the shopper's captured ad click onto the order(s) they just placed.
 *
 * Runs on checkout_submit_all_after because the order already has its ID there, for the storefront, REST and
 * GraphQL checkouts alike.
 */
class RecordOrderClickObserver implements ObserverInterface
{
    /**
     * @param CookieManagerInterface $cookieManager
     * @param CookieParser $cookieParser
     * @param OrderClick $orderClick
     * @param Config $config
     * @param State $appState
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieParser $cookieParser,
        private readonly OrderClick $orderClick,
        private readonly Config $config,
        private readonly State $appState,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        try {
            // An order keyed in by staff must not inherit a click from the admin's own browser.
            if ($this->appState->getAreaCode() === Area::AREA_ADMINHTML) {
                return;
            }
            $click = $this->cookieParser->parse($this->cookieManager->getCookie(CookieParser::COOKIE_NAME));
            if ($click === null) {
                return;
            }

            $orders = $observer->getEvent()->getData('orders') ?: [$observer->getEvent()->getData('order')];
            foreach (array_filter($orders) as $order) {
                if ($order->getId() && $this->config->isTrackingEnabled($order->getStoreId())) {
                    $this->orderClick->save((int) $order->getId(), $click);
                }
            }
        } catch (\Throwable $e) {
            // Attribution is never worth failing a checkout for.
            $this->logger->error(
                'UpturnStudio_GoogleFeed: could not record order click - ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
