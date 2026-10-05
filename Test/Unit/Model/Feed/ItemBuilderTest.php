<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Test\Unit\Model\Feed;

use Magento\Catalog\Model\Product;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use UpturnStudio\GoogleFeed\Model\Feed;
use UpturnStudio\GoogleFeed\Model\Feed\GoogleAttributePool;
use UpturnStudio\GoogleFeed\Model\Feed\ItemBuilder;
use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;
use UpturnStudio\GoogleFeed\Model\Feed\ValueResolver;
use UpturnStudio\GoogleFeed\Model\Feed\ValueSanitizer;

/**
 * @covers \UpturnStudio\GoogleFeed\Model\Feed\ItemBuilder
 */
class ItemBuilderTest extends TestCase
{
    private const MAPPING = [
        ['google_attribute' => 'id', 'value' => 'SKU-1'],
        ['google_attribute' => 'title', 'value' => 'Mapped title'],
        ['google_attribute' => 'link', 'value' => 'https://example.test/p.html'],
        ['google_attribute' => 'brand', 'value' => 'Mapped brand'],
        ['google_attribute' => 'Not Valid', 'value' => 'ignored'],
    ];

    private ItemBuilder $itemBuilder;

    private ProductContext $context;

    protected function setUp(): void
    {
        // The resolver is stubbed to echo each row's value, so the tests are about merging, not resolving.
        $valueResolver = $this->createMock(ValueResolver::class);
        $valueResolver->method('resolve')->willReturnCallback(
            static fn (array $row): array => ($row['value'] ?? '') === '' ? [] : [$row['value']]
        );

        $this->itemBuilder = new ItemBuilder($valueResolver, new ValueSanitizer(), new GoogleAttributePool());
        $this->context = new ProductContext(
            $this->createMock(Product::class),
            null,
            $this->createMock(StoreInterface::class),
            $this->createMock(Feed::class),
            true
        );
    }

    public function testBuildsFromMappingAndSkipsMalformedCodes(): void
    {
        $item = $this->itemBuilder->build($this->context, self::MAPPING);

        $this->assertSame(['SKU-1'], $item['id']);
        $this->assertSame(['Mapped brand'], $item['brand']);
        $this->assertArrayNotHasKey('Not Valid', $item);
    }

    public function testOverridesWinAndCanAddAttributesOutsideTheMapping(): void
    {
        $item = $this->itemBuilder->build($this->context, self::MAPPING, [
            'title' => 'Pushed <i>title</i>',
            'custom_label_0' => 'low-roas',
        ]);

        $this->assertSame(['Pushed title'], $item['title']);
        $this->assertSame(['low-roas'], $item['custom_label_0']);
    }

    public function testEmptyOverrideRemovesTheMappedAttribute(): void
    {
        $item = $this->itemBuilder->build($this->context, self::MAPPING, ['brand' => '']);

        $this->assertArrayNotHasKey('brand', $item);
    }

    public function testUrlOverridesAreNotTreatedAsMarkupAndImagesSplitOnCommas(): void
    {
        $item = $this->itemBuilder->build($this->context, self::MAPPING, [
            'link' => 'https://example.test/p.html?a=1&times=2',
            'additional_image_link' => 'https://example.test/1.jpg, https://example.test/2.jpg',
        ]);

        $this->assertSame(['https://example.test/p.html?a=1&times=2'], $item['link']);
        $this->assertSame(['https://example.test/1.jpg', 'https://example.test/2.jpg'], $item['additional_image_link']);
    }

    public function testItemWithoutIdOrLinkIsNotListed(): void
    {
        $this->assertNull($this->itemBuilder->build($this->context, self::MAPPING, ['link' => '']));
        $this->assertNull($this->itemBuilder->build($this->context, self::MAPPING, ['id' => '']));
    }

    public function testIdentifierExistsIsDeclaredOnlyWhenIdentifiersAreMissing(): void
    {
        $this->assertSame(['no'], $this->itemBuilder->build($this->context, self::MAPPING)['identifier_exists']);

        $withGtin = $this->itemBuilder->build($this->context, self::MAPPING, ['gtin' => '00012345678905']);
        $this->assertArrayNotHasKey('identifier_exists', $withGtin);

        $withMpn = $this->itemBuilder->build($this->context, self::MAPPING, ['mpn' => 'MB-01']);
        $this->assertArrayNotHasKey('identifier_exists', $withMpn);

        $explicit = $this->itemBuilder->build($this->context, self::MAPPING, ['identifier_exists' => 'yes']);
        $this->assertSame(['yes'], $explicit['identifier_exists']);
    }

    public function testTitleIsCutToGooglesLimit(): void
    {
        $item = $this->itemBuilder->build($this->context, self::MAPPING, ['title' => str_repeat('a', 200)]);

        $this->assertSame(150, mb_strlen($item['title'][0]));
    }
}
