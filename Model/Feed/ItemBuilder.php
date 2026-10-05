<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

/**
 * Builds the attribute values of one feed item from the mapping and any pushed overrides.
 */
class ItemBuilder
{
    /**
     * Google's length limits for free-text attributes.
     */
    private const MAX_LENGTHS = ['title' => 150, 'description' => 5000];

    /**
     * Attributes holding URLs, which must not be treated as markup.
     */
    private const URL_ATTRIBUTES = [
        'link',
        'mobile_link',
        'canonical_link',
        'ads_redirect',
        'image_link',
        'additional_image_link',
        'lifestyle_image_link',
    ];

    /**
     * Attributes that may repeat; a pushed value is split on commas.
     */
    private const MULTI_VALUE_ATTRIBUTES = ['additional_image_link', 'lifestyle_image_link'];

    /**
     * @param ValueResolver $valueResolver
     * @param ValueSanitizer $sanitizer
     * @param GoogleAttributePool $attributePool
     */
    public function __construct(
        private readonly ValueResolver $valueResolver,
        private readonly ValueSanitizer $sanitizer,
        private readonly GoogleAttributePool $attributePool
    ) {
    }

    /**
     * Values keyed by Google attribute code, or null when the item cannot be listed.
     *
     * Overrides win over the mapping. An override with an empty value removes the attribute from the item.
     *
     * @param ProductContext $context
     * @param array[] $mapping
     * @param string[] $overrides Google attribute code => value
     * @return string[][]|null
     */
    public function build(ProductContext $context, array $mapping, array $overrides = []): ?array
    {
        $item = [];
        foreach ($mapping as $row) {
            $code = (string) ($row['google_attribute'] ?? '');
            if (!$this->attributePool->isValidCode($code)) {
                continue;
            }
            $values = $this->valueResolver->resolve($row, $context);
            if ($values) {
                $item[$code] = $values;
            }
        }

        foreach ($overrides as $code => $value) {
            $code = (string) $code;
            if (!$this->attributePool->isValidCode($code)) {
                continue;
            }
            $values = $this->normaliseOverride($code, (string) $value);
            if ($values) {
                $item[$code] = $values;
            } else {
                unset($item[$code]);
            }
        }

        // Google needs an ID to track the item and a page to send the shopper to.
        if (empty($item['id']) || empty($item['link'])) {
            return null;
        }

        foreach (self::MAX_LENGTHS as $code => $maxLength) {
            if (isset($item[$code])) {
                $item[$code] = [$this->sanitizer->clean($item[$code][0], $maxLength)];
            }
        }

        if (!isset($item['identifier_exists']) && !$this->hasIdentifier($item)) {
            $item['identifier_exists'] = ['no'];
        }

        return $item;
    }

    /**
     * Whether the item carries a GTIN, or a brand together with an MPN.
     *
     * @param string[][] $item
     * @return bool
     */
    private function hasIdentifier(array $item): bool
    {
        return !empty($item['gtin']) || (!empty($item['mpn']) && !empty($item['brand']));
    }

    /**
     * Clean a pushed value and split it when the attribute may repeat.
     *
     * @param string $code
     * @param string $value
     * @return string[]
     */
    private function normaliseOverride(string $code, string $value): array
    {
        $parts = in_array($code, self::MULTI_VALUE_ATTRIBUTES, true) ? explode(',', $value) : [$value];

        $values = [];
        foreach ($parts as $part) {
            $part = in_array($code, self::URL_ATTRIBUTES, true) ? trim($part) : $this->sanitizer->clean($part);
            if ($part !== '') {
                $values[] = $part;
            }
        }

        return $values;
    }
}
