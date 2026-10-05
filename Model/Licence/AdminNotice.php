<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Licence;

use Magento\Framework\Notification\MessageInterface;

/**
 * Admin system message shown when the subscription key has been rejected.
 */
class AdminNotice implements MessageInterface
{
    /**
     * @param Status $status
     */
    public function __construct(
        private readonly Status $status
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getIdentity()
    {
        return 'upturnstudio_googlefeed_subscription';
    }

    /**
     * @inheritdoc
     */
    public function isDisplayed()
    {
        return $this->status->getState() === Status::STATE_INVALID;
    }

    /**
     * @inheritdoc
     */
    public function getText()
    {
        return (string) __(
            'The Google Feeds subscription key was not recognised, so content suggestions and ad spend reporting '
            . 'are not available. Feeds, click capture and the API are not affected. Update the key under '
            . 'Stores > Configuration > UpturnStudio > Google Feeds.'
        );
    }

    /**
     * @inheritdoc
     */
    public function getSeverity()
    {
        return self::SEVERITY_MINOR;
    }
}
