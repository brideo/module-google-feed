<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Stock;

use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Salability of a chunk of SKUs in the stock assigned to a store's website.
 */
class SalabilityProvider
{
    /**
     * @var int[] Website ID => stock ID
     */
    private array $stockIds = [];

    /**
     * @param AreProductsSalableInterface $areProductsSalable
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly AreProductsSalableInterface $areProductsSalable,
        private readonly StockResolverInterface $stockResolver,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Whether each SKU can currently be bought on the website.
     *
     * @param string[] $skus
     * @param int $websiteId
     * @return bool[] Lower-cased SKU => salable
     */
    public function getSalableMap(array $skus, int $websiteId): array
    {
        if (!$skus) {
            return [];
        }
        $map = [];
        foreach ($this->areProductsSalable->execute(array_values($skus), $this->getStockId($websiteId)) as $result) {
            $map[mb_strtolower($result->getSku())] = $result->isSalable();
        }

        return $map;
    }

    /**
     * Stock assigned to the website's sales channel.
     *
     * @param int $websiteId
     * @return int
     */
    private function getStockId(int $websiteId): int
    {
        if (!isset($this->stockIds[$websiteId])) {
            $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
            $this->stockIds[$websiteId] = (int) $this->stockResolver
                ->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode)
                ->getStockId();
        }

        return $this->stockIds[$websiteId];
    }
}
