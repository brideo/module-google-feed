<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Management;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use UpturnStudio\GoogleFeed\Model\Feed;
use UpturnStudio\GoogleFeed\Model\Feed\GoogleAttributePool;
use UpturnStudio\GoogleFeed\Model\Feed\Resolver\ResolverPool;
use UpturnStudio\GoogleFeed\Model\Feed\ValueResolver;
use UpturnStudio\GoogleFeed\Model\Source\ProductType;

/**
 * Checks a complete feed definition and reports every problem at once.
 */
class FeedValidator
{
    private const NAME_LIMIT = 255;

    private const VISIBILITIES = [
        Visibility::VISIBILITY_IN_CATALOG,
        Visibility::VISIBILITY_IN_SEARCH,
        Visibility::VISIBILITY_BOTH,
    ];

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScheduleFactory $scheduleFactory
     * @param GoogleAttributePool $attributePool
     * @param ResolverPool $resolverPool
     * @param EavConfig $eavConfig
     * @param ProductType $productTypeSource
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ScheduleFactory $scheduleFactory,
        private readonly GoogleAttributePool $attributePool,
        private readonly ResolverPool $resolverPool,
        private readonly EavConfig $eavConfig,
        private readonly ProductType $productTypeSource
    ) {
    }

    /**
     * Problems with a complete, normalised feed definition; empty when it is valid.
     *
     * @param array $feed
     * @return string[]
     */
    public function validate(array $feed): array
    {
        $errors = [];

        $name = (string) ($feed['name'] ?? '');
        if ($name === '') {
            $errors[] = 'A feed name is required.';
        } elseif (mb_strlen($name) > self::NAME_LIMIT) {
            $errors[] = sprintf('The feed name is limited to %d characters.', self::NAME_LIMIT);
        }

        if (!$this->storeExists((int) ($feed['store_id'] ?? 0))) {
            $errors[] = sprintf('store_id %d is not a store view.', (int) ($feed['store_id'] ?? 0));
        }

        $country = $feed['target_country'] ?? null;
        if ($country !== null && !preg_match('/^[A-Z]{2}$/', (string) $country)) {
            $errors[] = 'target_country must be a two-letter country code such as US.';
        }

        if (!in_array($feed['price_tax'] ?? '', [
            Feed::PRICE_TAX_AUTO,
            Feed::PRICE_TAX_INCLUDING,
            Feed::PRICE_TAX_EXCLUDING,
        ], true)) {
            $errors[] = 'price_tax must be auto, incl or excl.';
        }

        $cron = $feed['cron_expression'] ?? null;
        if ($cron !== null && !$this->isValidCron((string) $cron)) {
            $errors[] = sprintf('"%s" is not a valid cron expression.', $cron);
        }

        array_push($errors, ...$this->validateFilters((array) ($feed['filters'] ?? [])));
        array_push($errors, ...$this->validateMapping((array) ($feed['mapping'] ?? [])));

        return $errors;
    }

    /**
     * Problems with mapping rows; empty when they are valid.
     *
     * @param array[] $rows
     * @return string[]
     */
    public function validateMapping(array $rows): array
    {
        $errors = [];
        foreach ($rows as $index => $row) {
            $code = (string) ($row['google_attribute'] ?? '');
            $source = (string) ($row['source'] ?? '');
            $label = sprintf('Mapping row %d (%s)', $index + 1, $code === '' ? 'no attribute' : $code);

            if (!$this->attributePool->isValidCode($code)) {
                $errors[] = sprintf('%s: google_attribute must be lower-case letters, digits and underscores.', $label);
            }
            $sourceError = $this->validateSource($source, (string) ($row['value'] ?? ''));
            if ($sourceError !== null) {
                $errors[] = sprintf('%s: %s', $label, $sourceError);
            }
        }

        return $errors;
    }

    /**
     * @param array $filters
     * @return string[]
     */
    private function validateFilters(array $filters): array
    {
        $errors = [];
        $knownTypes = array_column($this->productTypeSource->toOptionArray(), 'value');
        foreach ((array) ($filters['product_types'] ?? []) as $type) {
            if (!in_array($type, $knownTypes, true)) {
                $errors[] = sprintf(
                    'Product type "%s" cannot be listed. Use one of: %s. Configurable products are always listed '
                    . 'through their variants.',
                    $type,
                    implode(', ', $knownTypes)
                );
            }
        }
        foreach ((array) ($filters['visibility'] ?? []) as $visibility) {
            if (!in_array((int) $visibility, self::VISIBILITIES, true)) {
                $errors[] = sprintf('Visibility %d is not valid. Use 2 (catalog), 3 (search) or 4 (both).', $visibility);
            }
        }

        return $errors;
    }

    /**
     * @param string $source
     * @param string $value
     * @return string|null
     */
    private function validateSource(string $source, string $value): ?string
    {
        if ($source === '') {
            return 'source is required.';
        }
        $parts = explode(':', $source, 2);
        $type = $parts[0];
        $code = $parts[1] ?? '';

        return match ($type) {
            ValueResolver::SOURCE_STATIC => trim($value) === '' ? 'a static source needs a value.' : null,
            ValueResolver::SOURCE_TEMPLATE => trim($value) === '' ? 'a template source needs a value.' : null,
            ValueResolver::SOURCE_RESOLVER => $this->resolverPool->get($code) === null
                ? sprintf('"%s" is not a built-in source. Use one of: %s.', $source, $this->resolverCodes())
                : null,
            ValueResolver::SOURCE_ATTRIBUTE => $code !== '' && $this->eavConfig->getAttribute(Product::ENTITY, $code)->getId()
                ? null
                : sprintf('"%s" is not a product attribute of this store.', $code),
            default => sprintf(
                'source "%s" is not valid. Use static, template, resolver:<code> or attribute:<code>.',
                $source
            ),
        };
    }

    /**
     * @return string
     */
    private function resolverCodes(): string
    {
        return implode(', ', array_map(
            static fn (string $code): string => 'resolver:' . $code,
            array_keys($this->resolverPool->getAll())
        ));
    }

    /**
     * @param int $storeId
     * @return bool
     */
    private function storeExists(int $storeId): bool
    {
        if ($storeId <= 0) {
            return false;
        }
        try {
            $this->storeManager->getStore($storeId);

            return true;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @param string $expression
     * @return bool
     */
    private function isValidCron(string $expression): bool
    {
        try {
            $this->scheduleFactory->create()->setCronExpr($expression)->setScheduledAt(time())->trySchedule();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
