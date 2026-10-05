<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\ResourceModel\Feed;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use UpturnStudio\GoogleFeed\Model\Feed;
use UpturnStudio\GoogleFeed\Model\ResourceModel\Feed as FeedResource;

/**
 * Feed collection.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'feed_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Feed::class, FeedResource::class);
    }
}
