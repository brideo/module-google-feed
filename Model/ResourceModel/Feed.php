<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Feed resource model.
 */
class Feed extends AbstractDb
{
    public const TABLE = 'upturnstudio_googlefeed_feed';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, 'feed_id');
    }

    /**
     * Update run-state columns only, leaving a definition that is being edited untouched.
     *
     * @param int $feedId
     * @param array $bind
     * @return void
     */
    public function updateRunState(int $feedId, array $bind): void
    {
        $this->getConnection()->update($this->getMainTable(), $bind, ['feed_id = ?' => $feedId]);
    }
}
