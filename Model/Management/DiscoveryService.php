<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Management;

use Magento\Store\Model\StoreManagerInterface;
use UpturnStudio\GoogleFeed\Model\Feed\DefaultMappingProvider;
use UpturnStudio\GoogleFeed\Model\Feed\GoogleAttributePool;
use UpturnStudio\GoogleFeed\Model\Source\MappingSource;
use UpturnStudio\GoogleFeed\Model\Source\PriceTax;
use UpturnStudio\GoogleFeed\Model\Source\ProductType;
use UpturnStudio\GoogleFeed\Model\Source\Visibility;

/**
 * Lists what a feed can be built from, so a client can create valid feeds without guessing.
 */
class DiscoveryService
{
    /**
     * @param GoogleAttributePool $attributePool
     * @param MappingSource $mappingSource
     * @param ProductType $productType
     * @param Visibility $visibility
     * @param PriceTax $priceTax
     * @param StoreManagerInterface $storeManager
     * @param DefaultMappingProvider $defaultMappingProvider
     * @param FeedInputNormalizer $normalizer
     */
    public function __construct(
        private readonly GoogleAttributePool $attributePool,
        private readonly MappingSource $mappingSource,
        private readonly ProductType $productType,
        private readonly Visibility $visibility,
        private readonly PriceTax $priceTax,
        private readonly StoreManagerInterface $storeManager,
        private readonly DefaultMappingProvider $defaultMappingProvider,
        private readonly FeedInputNormalizer $normalizer
    ) {
    }

    /**
     * Everything a client needs to build a feed.
     *
     * @return array
     */
    public function get(): array
    {
        $googleAttributes = [];
        foreach ($this->attributePool->getAll() as $code => $definition) {
            $googleAttributes[] = [
                'code' => (string) $code,
                'label' => (string) ($definition['label'] ?? $code),
                'default_source' => ($definition['default_source'] ?? '') ?: null,
                'default_value' => ($definition['default_value'] ?? '') ?: null,
                'default_use_parent' => !empty($definition['default_use_parent']),
            ];
        }

        $storeViews = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeViews[] = [
                'store_id' => (int) $store->getId(),
                'code' => (string) $store->getCode(),
                'name' => (string) $store->getName(),
                'website_name' => (string) $store->getWebsite()->getName(),
                'currency_code' => (string) $store->getDefaultCurrencyCode(),
            ];
        }

        $defaultMapping = array_map(static function (array $row): array {
            $row['use_parent'] = $row['use_parent'] === '1';

            return $row;
        }, $this->normalizer->normalizeMappingRows($this->defaultMappingProvider->get()));

        return [
            'google_attributes' => $googleAttributes,
            'mapping_sources' => $this->flatten($this->mappingSource->toOptionArray()),
            'product_types' => $this->flatten($this->productType->toOptionArray()),
            'visibilities' => $this->flatten($this->visibility->toOptionArray()),
            'price_tax_modes' => $this->flatten($this->priceTax->toOptionArray()),
            'store_views' => $storeViews,
            'default_mapping' => $defaultMapping,
        ];
    }

    /**
     * Flatten option groups into one list, carrying the group label on each option.
     *
     * @param array $options
     * @return array[]
     */
    private function flatten(array $options): array
    {
        $flat = [];
        foreach ($options as $option) {
            if (is_array($option['value'] ?? null)) {
                foreach ($option['value'] as $child) {
                    $flat[] = [
                        'value' => (string) $child['value'],
                        'label' => (string) $child['label'],
                        'group' => (string) $option['label'],
                    ];
                }
                continue;
            }
            $flat[] = ['value' => (string) $option['value'], 'label' => (string) $option['label'], 'group' => null];
        }

        return $flat;
    }
}
