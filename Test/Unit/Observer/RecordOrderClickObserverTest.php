<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Test\Unit\Observer;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use UpturnStudio\GoogleFeed\Model\Click\CookieParser;
use UpturnStudio\GoogleFeed\Model\Config;
use UpturnStudio\GoogleFeed\Model\ResourceModel\OrderClick;
use UpturnStudio\GoogleFeed\Observer\RecordOrderClickObserver;

/**
 * @covers \UpturnStudio\GoogleFeed\Observer\RecordOrderClickObserver
 */
class RecordOrderClickObserverTest extends TestCase
{
    /** @var CookieManagerInterface&MockObject */
    private CookieManagerInterface $cookieManager;

    /** @var OrderClick&MockObject */
    private OrderClick $orderClick;

    /** @var Config&MockObject */
    private Config $config;

    /** @var State&MockObject */
    private State $appState;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    private RecordOrderClickObserver $observer;

    protected function setUp(): void
    {
        $this->cookieManager = $this->createMock(CookieManagerInterface::class);
        $this->orderClick = $this->createMock(OrderClick::class);
        $this->config = $this->createMock(Config::class);
        $this->appState = $this->createMock(State::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->observer = new RecordOrderClickObserver(
            $this->cookieManager,
            new CookieParser(),
            $this->orderClick,
            $this->config,
            $this->appState,
            $this->logger
        );
    }

    public function testRecordsTheClickOnTheOrder(): void
    {
        $this->appState->method('getAreaCode')->willReturn(Area::AREA_WEBAPI_REST);
        $this->cookieManager->method('getCookie')->willReturn(json_encode(['gclid' => 'abc123', 'sku' => 'SKU-1']));
        $this->config->method('isTrackingEnabled')->willReturn(true);

        $this->orderClick->expects($this->once())->method('save')->with(
            42,
            $this->callback(static fn (array $click): bool
                => $click['gclid'] === 'abc123' && $click['landing_sku'] === 'SKU-1')
        );

        $this->observer->execute($this->buildObserver(['order' => $this->buildOrder(42)]));
    }

    public function testAClickAttachedToTheCartBeatsTheCookie(): void
    {
        $this->appState->method('getAreaCode')->willReturn(Area::AREA_WEBAPI_REST);
        $this->cookieManager->method('getCookie')->willReturn(json_encode(['gclid' => 'from-cookie']));
        $this->config->method('isTrackingEnabled')->willReturn(true);
        $this->orderClick->method('getQuoteClick')->with(55)->willReturn(['gclid' => 'from-cart', 'landing_sku' => 'SKU-9']);

        $this->orderClick->expects($this->once())->method('save')->with(
            42,
            $this->callback(static fn (array $click): bool => $click['gclid'] === 'from-cart')
        );

        $order = $this->buildOrder(42);
        $order->method('getQuoteId')->willReturn(55);
        $this->observer->execute($this->buildObserver(['order' => $order]));
    }

    public function testRecordsEveryOrderOfAMultishippingCheckout(): void
    {
        $this->appState->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $this->cookieManager->method('getCookie')->willReturn(json_encode(['gclid' => 'abc123']));
        $this->config->method('isTrackingEnabled')->willReturn(true);

        $this->orderClick->expects($this->exactly(2))->method('save');

        $this->observer->execute($this->buildObserver(['orders' => [$this->buildOrder(1), $this->buildOrder(2)]]));
    }

    public function testIgnoresOrdersCreatedInTheAdmin(): void
    {
        $this->appState->method('getAreaCode')->willReturn(Area::AREA_ADMINHTML);
        $this->cookieManager->method('getCookie')->willReturn(json_encode(['gclid' => 'abc123']));

        $this->orderClick->expects($this->never())->method('save');

        $this->observer->execute($this->buildObserver(['order' => $this->buildOrder(42)]));
    }

    public function testDoesNothingWithoutAClickOrWhenTrackingIsOff(): void
    {
        $this->appState->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $this->orderClick->expects($this->never())->method('save');

        $this->cookieManager->method('getCookie')->willReturnOnConsecutiveCalls(
            null,
            json_encode(['gclid' => 'abc123'])
        );
        $this->config->method('isTrackingEnabled')->willReturn(false);

        $this->observer->execute($this->buildObserver(['order' => $this->buildOrder(42)]));
        $this->observer->execute($this->buildObserver(['order' => $this->buildOrder(42)]));
    }

    public function testAStorageFailureNeverBreaksCheckout(): void
    {
        $this->appState->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);
        $this->cookieManager->method('getCookie')->willReturn(json_encode(['gclid' => 'abc123']));
        $this->config->method('isTrackingEnabled')->willReturn(true);
        $this->orderClick->method('save')->willThrowException(new \RuntimeException('db down'));

        $this->logger->expects($this->once())->method('error');

        $this->observer->execute($this->buildObserver(['order' => $this->buildOrder(42)]));
    }

    /**
     * @param int $id
     * @return Order&MockObject
     */
    private function buildOrder(int $id): Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn($id);
        $order->method('getStoreId')->willReturn(1);

        return $order;
    }

    /**
     * @param array $eventData
     * @return Observer
     */
    private function buildObserver(array $eventData): Observer
    {
        $observer = new Observer();
        $observer->setEvent(new Event($eventData));

        return $observer;
    }
}
