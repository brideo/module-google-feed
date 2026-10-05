<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\ViewModel;

use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use UpturnStudio\GoogleFeed\Model\Click\CookieParser;
use UpturnStudio\GoogleFeed\Model\Config;

/**
 * Settings the storefront click-capture script reads from the page.
 */
class ClickCapture implements ArgumentInterface
{
    /**
     * @param Config $config
     * @param CatalogHelper $catalogHelper
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly Config $config,
        private readonly CatalogHelper $catalogHelper,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * Name of the first-party cookie.
     *
     * @return string
     */
    public function getCookieName(): string
    {
        return CookieParser::COOKIE_NAME;
    }

    /**
     * Attribution window in days.
     *
     * @return int
     */
    public function getCookieLifetimeDays(): int
    {
        return $this->config->getCookieLifetimeDays();
    }

    /**
     * Whether the script must wait for cookie consent.
     *
     * @return bool
     */
    public function isConsentRequired(): bool
    {
        return $this->config->isConsentRequired();
    }

    /**
     * SKU of the product page being viewed, empty elsewhere.
     *
     * @return string
     */
    public function getLandingSku(): string
    {
        if ($this->request->getFullActionName() !== 'catalog_product_view') {
            return '';
        }

        return (string) $this->catalogHelper->getProduct()?->getSku();
    }
}
