<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Api;

use Magento\Store\Model\StoreManagerInterface;
use UpturnStudio\GoogleFeed\Api\Data\FeedInfoInterfaceFactory;
use UpturnStudio\GoogleFeed\Api\FeedListInterface;
use UpturnStudio\GoogleFeed\Model\FeedRepository;
use UpturnStudio\GoogleFeed\Model\FeedUrlProvider;

/**
 * @inheritdoc
 */
class FeedList implements FeedListInterface
{
    /**
     * @param FeedRepository $feedRepository
     * @param FeedUrlProvider $feedUrlProvider
     * @param StoreManagerInterface $storeManager
     * @param FeedInfoInterfaceFactory $feedInfoFactory
     */
    public function __construct(
        private readonly FeedRepository $feedRepository,
        private readonly FeedUrlProvider $feedUrlProvider,
        private readonly StoreManagerInterface $storeManager,
        private readonly FeedInfoInterfaceFactory $feedInfoFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getList(): array
    {
        $result = [];
        foreach ($this->feedRepository->getAll() as $feed) {
            $store = $this->storeManager->getStore($feed->getStoreId());
            $result[] = $this->feedInfoFactory->create(['data' => [
                'feed_id' => (int) $feed->getId(),
                'name' => $feed->getName(),
                'store_id' => $feed->getStoreId(),
                'store_code' => $store->getCode(),
                'target_country' => $feed->getData('target_country') ?: null,
                'currency_code' => $store->getDefaultCurrencyCode(),
                'url' => $this->feedUrlProvider->getUrl($feed),
                'is_active' => $feed->isActive(),
                'last_status' => $feed->getData('last_status'),
                'last_generated_at' => $feed->getData('last_generated_at'),
                'product_count' => $feed->getData('product_count'),
            ]]);
        }

        return $result;
    }
}
