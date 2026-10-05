<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Management;

use UpturnStudio\GoogleFeed\Model\Feed;
use UpturnStudio\GoogleFeed\Model\FeedUrlProvider;

/**
 * Converts a feed to the plain arrays every channel works with.
 */
class FeedMapper
{
    private const FILTER_DEFAULTS = [
        'category_ids' => [],
        'product_types' => [],
        'visibility' => [],
        'attribute_set_ids' => [],
        'exclude_out_of_stock' => false,
    ];

    /**
     * @param FeedUrlProvider $feedUrlProvider
     */
    public function __construct(
        private readonly FeedUrlProvider $feedUrlProvider
    ) {
    }

    /**
     * The editable part of the feed, complete and normalised.
     *
     * @param Feed $feed
     * @return array
     */
    public function toStorable(Feed $feed): array
    {
        $filters = array_replace(self::FILTER_DEFAULTS, $feed->getFilters());
        $filters['category_ids'] = array_map('intval', (array) $filters['category_ids']);
        $filters['visibility'] = array_map('intval', (array) $filters['visibility']);
        $filters['attribute_set_ids'] = array_map('intval', (array) $filters['attribute_set_ids']);
        $filters['product_types'] = array_values((array) $filters['product_types']);
        $filters['exclude_out_of_stock'] = (bool) $filters['exclude_out_of_stock'];

        return [
            'name' => $feed->getName(),
            'store_id' => $feed->getStoreId(),
            'is_active' => $feed->isActive(),
            'target_country' => $feed->getData('target_country') ?: null,
            'price_tax' => $feed->getPriceTax(),
            'cron_expression' => $feed->getCronExpression() ?: null,
            'filters' => array_intersect_key($filters, self::FILTER_DEFAULTS),
            'mapping' => array_map(static fn (array $row): array => [
                'google_attribute' => (string) ($row['google_attribute'] ?? ''),
                'source' => (string) ($row['source'] ?? ''),
                'value' => (string) ($row['value'] ?? ''),
                'fallback' => (string) ($row['fallback'] ?? ''),
                'use_parent' => !empty($row['use_parent']) && $row['use_parent'] !== '0' ? '1' : '0',
            ], $feed->getMapping()),
        ];
    }

    /**
     * The whole feed as returned to API clients, with snake_case keys.
     *
     * @param Feed $feed
     * @return array
     */
    public function toArray(Feed $feed): array
    {
        $data = $this->toStorable($feed);
        $data['mapping'] = array_map(static function (array $row): array {
            $row['use_parent'] = $row['use_parent'] === '1';

            return $row;
        }, $data['mapping']);

        return ['feed_id' => (int) $feed->getId()] + $data + [
            'url' => $feed->getToken() === '' ? null : $this->feedUrlProvider->getUrl($feed),
            'last_status' => $feed->getData('last_status'),
            'last_message' => $feed->getData('last_message'),
            'product_count' => $feed->getData('product_count') === null ? null : (int) $feed->getData('product_count'),
            'last_generated_at' => $feed->getData('last_generated_at'),
            'created_at' => $feed->getData('created_at'),
            'updated_at' => $feed->getData('updated_at'),
        ];
    }

    /**
     * Write a storable definition onto the feed model.
     *
     * @param Feed $feed
     * @param array $storable
     * @return void
     */
    public function apply(Feed $feed, array $storable): void
    {
        $feed->addData([
            'name' => $storable['name'],
            'store_id' => $storable['store_id'],
            'is_active' => $storable['is_active'] ? 1 : 0,
            'target_country' => $storable['target_country'],
            'price_tax' => $storable['price_tax'],
            'cron_expression' => $storable['cron_expression'],
            'filters' => json_encode($storable['filters']),
            'mapping' => json_encode($storable['mapping']),
        ]);
    }
}
