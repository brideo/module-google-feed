<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Tax\Model\Config as TaxConfig;
use UpturnStudio\GoogleFeed\Model\Feed;

/**
 * Regular and final prices for a feed item, in the store's currency and with tax as the feed asks for it.
 */
class PriceCalculator
{
    /**
     * Product types whose price is a range, so the lowest configuration is listed.
     */
    private const RANGE_TYPES = ['bundle', 'grouped', 'configurable'];

    /**
     * @param CatalogHelper $catalogHelper
     * @param TaxConfig $taxConfig
     */
    public function __construct(
        private readonly CatalogHelper $catalogHelper,
        private readonly TaxConfig $taxConfig
    ) {
    }

    /**
     * Regular and final price of the item.
     *
     * @param ProductContext $context
     * @return float[] With keys "regular" and "final"
     */
    public function getPrices(ProductContext $context): array
    {
        $product = $context->product;
        $priceInfo = $product->getPriceInfo();

        if (in_array($product->getTypeId(), self::RANGE_TYPES, true)) {
            // Minimal amounts already carry the store's tax display adjustments.
            $minimal = (float) $priceInfo->getPrice(FinalPrice::PRICE_CODE)->getMinimalPrice()->getValue();

            return ['regular' => $minimal, 'final' => $minimal];
        }

        $regular = (float) $priceInfo->getPrice(RegularPrice::PRICE_CODE)->getValue();
        $final = (float) $priceInfo->getPrice(FinalPrice::PRICE_CODE)->getValue();
        $includingTax = $this->isIncludingTax($context);

        return [
            'regular' => $this->applyTax($context, $product, $regular, $includingTax),
            'final' => $this->applyTax($context, $product, $final, $includingTax),
        ];
    }

    /**
     * Format an amount the way Google expects, e.g. "12.50 GBP".
     *
     * @param float $amount
     * @param string $currencyCode
     * @return string
     */
    public function format(float $amount, string $currencyCode): string
    {
        return number_format($amount, 2, '.', '') . ' ' . $currencyCode;
    }

    /**
     * Whether the feed lists prices including tax.
     *
     * @param ProductContext $context
     * @return bool
     */
    private function isIncludingTax(ProductContext $context): bool
    {
        return match ($context->feed->getPriceTax()) {
            Feed::PRICE_TAX_INCLUDING => true,
            Feed::PRICE_TAX_EXCLUDING => false,
            default => $this->taxConfig->getPriceDisplayType($context->store) !== TaxConfig::DISPLAY_TYPE_EXCLUDING_TAX,
        };
    }

    /**
     * Add or remove tax according to the store's tax rules.
     *
     * @param ProductContext $context
     * @param Product $product
     * @param float $price
     * @param bool $includingTax
     * @return float
     */
    private function applyTax(ProductContext $context, Product $product, float $price, bool $includingTax): float
    {
        if ($price <= 0) {
            return 0.0;
        }

        return (float) $this->catalogHelper->getTaxPrice(
            $product,
            $price,
            $includingTax,
            null,
            null,
            null,
            $context->store,
            null,
            true
        );
    }
}
