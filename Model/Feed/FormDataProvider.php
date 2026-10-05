<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

use Magento\Framework\Api\Filter;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use UpturnStudio\GoogleFeed\Model\Feed;
use UpturnStudio\GoogleFeed\Model\FeedUrlProvider;
use UpturnStudio\GoogleFeed\Model\ResourceModel\Feed\CollectionFactory;

/**
 * Feeds the admin form: unpacks the JSON columns into form fields and pre-fills a new feed's mapping.
 */
class FormDataProvider extends AbstractDataProvider
{
    /**
     * @var array|null
     */
    private ?array $loadedData = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param DefaultMappingProvider $defaultMappingProvider
     * @param FeedUrlProvider $feedUrlProvider
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly RequestInterface $request,
        private readonly DefaultMappingProvider $defaultMappingProvider,
        private readonly FeedUrlProvider $feedUrlProvider,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * The form looks the record up by request ID itself, so the generic collection filter is not needed.
     *
     * @param Filter $filter
     * @return void
     */
    public function addFilter(Filter $filter)
    {
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $feedId = (int) $this->request->getParam($this->getRequestFieldName());
        /** @var Feed|null $feed */
        $feed = $feedId ? $this->collection->getItemById($feedId) : null;
        if ($feed === null) {
            // A new record is keyed by an empty ID.
            return $this->loadedData = ['' => [
                'is_active' => '1',
                'price_tax' => Feed::PRICE_TAX_AUTO,
                'cron_expression' => '0 2 * * *',
                'product_types' => Generator::DEFAULT_TYPES,
                'mapping' => $this->defaultMappingProvider->get(),
            ]];
        }

        $filters = $feed->getFilters();
        $data = $feed->getData();
        unset($data['filters']);
        $data['mapping'] = $feed->getMapping();
        $data['category_ids'] = array_map('strval', (array) ($filters['category_ids'] ?? []));
        $data['product_types'] = array_values((array) ($filters['product_types'] ?? []));
        $data['visibility'] = array_map('strval', (array) ($filters['visibility'] ?? []));
        $data['attribute_set_ids'] = array_map('strval', (array) ($filters['attribute_set_ids'] ?? []));
        $data['exclude_out_of_stock'] = empty($filters['exclude_out_of_stock']) ? '0' : '1';
        $data['feed_url'] = $this->feedUrlProvider->getUrl($feed);

        return $this->loadedData = [$feed->getId() => $data];
    }
}
