<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Observer;

use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use UpturnStudio\GoogleFeed\Model\Licence\Status;

/**
 * Re-checks the subscription as soon as the module's configuration is saved.
 */
class RevalidateSubscriptionObserver implements ObserverInterface
{
    /**
     * @param Status $status
     * @param ReinitableConfigInterface $config
     */
    public function __construct(
        private readonly Status $status,
        private readonly ReinitableConfigInterface $config
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        // Read the key that was just saved, not the one cached for this request.
        $this->config->reinit();
        $this->status->refresh();
    }
}
