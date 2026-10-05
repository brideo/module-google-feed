<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Management;

/**
 * Turns feed input from any channel (admin form, REST, GraphQL, MCP) into typed values.
 *
 * Only the keys that were supplied come back, so the same code serves partial updates. Nothing is validated here:
 * bad values are kept as they are so the validator can report them instead of them being silently changed.
 */
class FeedInputNormalizer
{
    private const MAPPING_FIELDS = ['google_attribute', 'source', 'value', 'fallback', 'use_parent'];

    /**
     * @param array $data
     * @return array
     */
    public function normalize(array $data): array
    {
        $out = [];
        if (array_key_exists('name', $data)) {
            $out['name'] = trim((string) $data['name']);
        }
        if (array_key_exists('store_id', $data)) {
            $out['store_id'] = (int) $data['store_id'];
        }
        if (array_key_exists('is_active', $data)) {
            $out['is_active'] = $this->toBool($data['is_active']);
        }
        if (array_key_exists('target_country', $data)) {
            $country = strtoupper(trim((string) $data['target_country']));
            $out['target_country'] = $country === '' ? null : $country;
        }
        if (array_key_exists('price_tax', $data)) {
            $out['price_tax'] = strtolower(trim((string) $data['price_tax'])) ?: 'auto';
        }
        if (array_key_exists('cron_expression', $data)) {
            $cron = trim((string) $data['cron_expression']);
            $out['cron_expression'] = $cron === '' ? null : $cron;
        }
        if (isset($data['filters']) && is_array($data['filters'])) {
            $out['filters'] = $this->normalizeFilters($data['filters']);
        }
        if (isset($data['mapping']) && is_array($data['mapping'])) {
            $out['mapping'] = $this->normalizeMappingRows($data['mapping']);
        }

        return $out;
    }

    /**
     * Clean mapping rows: keep only known fields, drop blank rows, and keep the last row for each attribute.
     *
     * @param array $rows
     * @return array[]
     */
    public function normalizeMappingRows(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $clean = [
                'google_attribute' => trim((string) ($row['google_attribute'] ?? '')),
                'source' => trim((string) ($row['source'] ?? '')),
                'value' => (string) ($row['value'] ?? ''),
                'fallback' => (string) ($row['fallback'] ?? ''),
                'use_parent' => $this->toBool($row['use_parent'] ?? false) ? '1' : '0',
            ];
            if ($clean['google_attribute'] === '' && $clean['source'] === '') {
                continue;
            }
            if ($clean['google_attribute'] === '') {
                $result[] = $clean;
                continue;
            }
            // A later row for the same attribute replaces the earlier one, in the earlier one's position.
            $existing = array_search($clean['google_attribute'], array_column($result, 'google_attribute'), true);
            if ($existing === false) {
                $result[] = $clean;
            } else {
                $result[$existing] = $clean;
            }
        }

        return array_values($result);
    }

    /**
     * Mapping fields, for callers that need to know which keys a row may carry.
     *
     * @return string[]
     */
    public function getMappingFields(): array
    {
        return self::MAPPING_FIELDS;
    }

    /**
     * @param array $filters
     * @return array
     */
    private function normalizeFilters(array $filters): array
    {
        $out = [];
        if (array_key_exists('category_ids', $filters)) {
            $out['category_ids'] = $this->intList($filters['category_ids']);
        }
        if (array_key_exists('product_types', $filters)) {
            $out['product_types'] = $this->stringList($filters['product_types']);
        }
        if (array_key_exists('visibility', $filters)) {
            $out['visibility'] = $this->intList($filters['visibility']);
        }
        if (array_key_exists('attribute_set_ids', $filters)) {
            $out['attribute_set_ids'] = $this->intList($filters['attribute_set_ids']);
        }
        if (array_key_exists('exclude_out_of_stock', $filters)) {
            $out['exclude_out_of_stock'] = $this->toBool($filters['exclude_out_of_stock']);
        }

        return $out;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * @param mixed $values
     * @return int[]
     */
    private function intList(mixed $values): array
    {
        return array_values(array_unique(array_filter(array_map('intval', (array) $values))));
    }

    /**
     * @param mixed $values
     * @return string[]
     */
    private function stringList(mixed $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) $values
        ))));
    }
}
