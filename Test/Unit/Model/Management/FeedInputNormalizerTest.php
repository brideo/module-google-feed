<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Test\Unit\Model\Management;

use PHPUnit\Framework\TestCase;
use UpturnStudio\GoogleFeed\Model\Management\FeedInputNormalizer;

/**
 * @covers \UpturnStudio\GoogleFeed\Model\Management\FeedInputNormalizer
 */
class FeedInputNormalizerTest extends TestCase
{
    private FeedInputNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new FeedInputNormalizer();
    }

    public function testOnlySuppliedKeysComeBack(): void
    {
        $this->assertSame([], $this->normalizer->normalize([]));
        $this->assertSame(['name' => 'Shop'], $this->normalizer->normalize(['name' => '  Shop ', 'ignored' => 1]));
    }

    public function testValuesFromAnAdminFormAreTyped(): void
    {
        $out = $this->normalizer->normalize([
            'store_id' => '2',
            'is_active' => '0',
            'target_country' => ' gb ',
            'price_tax' => '',
            'cron_expression' => '  ',
            'filters' => [
                'category_ids' => ['3', '3', '0', 'x'],
                'product_types' => ['simple', ' simple', ''],
                'visibility' => '4',
                'exclude_out_of_stock' => '1',
            ],
        ]);

        $this->assertSame(2, $out['store_id']);
        $this->assertFalse($out['is_active']);
        $this->assertSame('GB', $out['target_country']);
        $this->assertSame('auto', $out['price_tax']);
        $this->assertNull($out['cron_expression']);
        $this->assertSame([3], $out['filters']['category_ids']);
        $this->assertSame(['simple'], $out['filters']['product_types']);
        $this->assertSame([4], $out['filters']['visibility']);
        $this->assertTrue($out['filters']['exclude_out_of_stock']);
        $this->assertArrayNotHasKey('attribute_set_ids', $out['filters']);
    }

    public function testClearingAFieldIsDifferentFromLeavingItOut(): void
    {
        $out = $this->normalizer->normalize(['target_country' => '', 'filters' => ['category_ids' => []]]);

        $this->assertNull($out['target_country']);
        $this->assertSame([], $out['filters']['category_ids']);
    }

    public function testMappingRowsAreCleanedAndTheLastRowPerAttributeWins(): void
    {
        $rows = $this->normalizer->normalizeMappingRows([
            ['google_attribute' => ' title ', 'source' => 'static', 'value' => 'First', 'use_parent' => true, 'extra' => 'x'],
            ['google_attribute' => '', 'source' => ''],
            ['google_attribute' => 'brand', 'source' => 'attribute:manufacturer', 'use_parent' => '1'],
            ['google_attribute' => 'title', 'source' => 'static', 'value' => 'Second'],
            'not a row',
            ['google_attribute' => '', 'source' => 'static'],
        ]);

        $this->assertSame(['title', 'brand', ''], array_column($rows, 'google_attribute'));
        $this->assertSame('Second', $rows[0]['value']);
        $this->assertSame('0', $rows[0]['use_parent']);
        $this->assertSame('1', $rows[1]['use_parent']);
        $this->assertSame(['google_attribute', 'source', 'value', 'fallback', 'use_parent'], array_keys($rows[0]));
    }
}
