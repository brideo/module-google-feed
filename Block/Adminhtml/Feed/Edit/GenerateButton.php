<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Block\Adminhtml\Feed\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Queues the saved feed for generation.
 */
class GenerateButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getButtonData(): array
    {
        if (!$this->getFeedId()) {
            return [];
        }

        return [
            'label' => __('Generate Now'),
            'class' => 'action-secondary',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s', {\"data\": {}})",
                __('Queue this feed for generation using its last saved settings?'),
                $this->getUrl('*/*/generate', ['feed_id' => $this->getFeedId()])
            ),
            'sort_order' => 30,
        ];
    }
}
