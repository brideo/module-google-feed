<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use UpturnStudio\GoogleFeed\Model\Feed;

/**
 * Outcome of a feed generation run.
 */
class RunStatus implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Feed::STATUS_QUEUED, 'label' => __('Queued')],
            ['value' => Feed::STATUS_RUNNING, 'label' => __('Running')],
            ['value' => Feed::STATUS_SUCCESS, 'label' => __('Success')],
            ['value' => Feed::STATUS_ERROR, 'label' => __('Error')],
        ];
    }
}
