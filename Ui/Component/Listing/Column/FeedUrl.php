<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use UpturnStudio\GoogleFeed\Model\Feed;

/**
 * Public feed URL, shown once a feed has been generated.
 */
class FeedUrl extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly StoreManagerInterface $storeManager,
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
            $item[$this->getData('name')] = '';
            if (empty($item['last_generated_at']) || empty($item['token'])) {
                continue;
            }
            try {
                $item[$this->getData('name')] = $this->storeManager->getStore((int) $item['store_id'])
                        ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . Feed::DIRECTORY . '/' . $item['token'] . '.xml';
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                continue;
            }
        }

        return $dataSource;
    }
}
