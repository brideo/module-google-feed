<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Block\Adminhtml\Feed\Edit;

use Magento\Backend\Block\Widget\Context;

/**
 * Shared helpers for the feed form buttons.
 */
class GenericButton
{
    /**
     * @param Context $context
     */
    public function __construct(
        private readonly Context $context
    ) {
    }

    /**
     * ID of the feed being edited, 0 for a new feed.
     *
     * @return int
     */
    protected function getFeedId(): int
    {
        return (int) $this->context->getRequest()->getParam('feed_id');
    }

    /**
     * Admin URL.
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    protected function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
