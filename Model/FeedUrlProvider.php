<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Builds the public URL Merchant Center fetches a feed from.
 */
class FeedUrlProvider
{
    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Public URL of the generated file.
     *
     * @param Feed $feed
     * @return string
     */
    public function getUrl(Feed $feed): string
    {
        $store = $this->storeManager->getStore($feed->getStoreId());

        return $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $feed->getRelativePath();
    }
}
