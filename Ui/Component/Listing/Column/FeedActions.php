<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Row actions of the feed grid.
 */
class FeedActions extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['feed_id'])) {
                continue;
            }
            $params = ['feed_id' => $item['feed_id']];
            $item[$this->getData('name')] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl('upturnstudio_googlefeed/feed/edit', $params),
                    'label' => __('Edit'),
                ],
                'generate' => [
                    'href' => $this->urlBuilder->getUrl('upturnstudio_googlefeed/feed/generate', $params),
                    'label' => __('Generate Now'),
                    // Without a confirmation the grid follows the link as a GET instead of posting.
                    'confirm' => [
                        'title' => __('Generate %1', $item['name'] ?? ''),
                        'message' => __('Queue this feed for generation using its last saved settings?'),
                    ],
                    'post' => true,
                ],
                'duplicate' => [
                    'href' => $this->urlBuilder->getUrl('upturnstudio_googlefeed/feed/duplicate', $params),
                    'label' => __('Duplicate'),
                    'confirm' => [
                        'title' => __('Duplicate %1', $item['name'] ?? ''),
                        'message' => __('Create an unscheduled copy of this feed?'),
                    ],
                    'post' => true,
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl('upturnstudio_googlefeed/feed/delete', $params),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete %1', $item['name'] ?? ''),
                        'message' => __('Delete this feed and its generated file?'),
                    ],
                    'post' => true,
                ],
            ];
        }

        return $dataSource;
    }
}
