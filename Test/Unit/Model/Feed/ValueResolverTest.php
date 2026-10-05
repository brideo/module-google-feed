<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Test\Unit\Model\Feed;

use Magento\Catalog\Model\Product;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UpturnStudio\GoogleFeed\Model\Feed;
use UpturnStudio\GoogleFeed\Model\Feed\AttributeValueReader;
use UpturnStudio\GoogleFeed\Model\Feed\ProductContext;
use UpturnStudio\GoogleFeed\Model\Feed\Resolver\ResolverInterface;
use UpturnStudio\GoogleFeed\Model\Feed\Resolver\ResolverPool;
use UpturnStudio\GoogleFeed\Model\Feed\ValueResolver;
use UpturnStudio\GoogleFeed\Model\Feed\ValueSanitizer;

/**
 * @covers \UpturnStudio\GoogleFeed\Model\Feed\ValueResolver
 */
class ValueResolverTest extends TestCase
{
    /** @var Product&MockObject */
    private Product $product;

    /** @var Product&MockObject */
    private Product $parent;

    /** @var string[][] Attribute values keyed by "child"/"parent" then attribute code */
    private array $values = [];

    private ValueResolver $valueResolver;

    protected function setUp(): void
    {
        $this->product = $this->createMock(Product::class);
        $this->parent = $this->createMock(Product::class);

        $reader = $this->createMock(AttributeValueReader::class);
        $reader->method('read')->willReturnCallback(
            fn (Product $product, string $code): string
                => $this->values[$product === $this->parent ? 'parent' : 'child'][$code] ?? ''
        );

        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->method('resolve')->willReturn(['https://example.test/a.jpg', 'https://example.test/b.jpg']);

        $this->valueResolver = new ValueResolver(
            new ResolverPool(['images' => $resolver]),
            $reader,
            new ValueSanitizer()
        );
    }

    public function testAttributeSourceReadsTheProduct(): void
    {
        $this->values = ['child' => ['name' => 'Child <b>Name</b>'], 'parent' => ['name' => 'Parent Name']];

        $this->assertSame(['Child Name'], $this->resolve(['source' => 'attribute:name']));
    }

    public function testPreferParentReadsTheParentFirst(): void
    {
        $this->values = ['child' => ['name' => 'Child Name'], 'parent' => ['name' => 'Parent Name']];

        $this->assertSame(['Parent Name'], $this->resolve(['source' => 'attribute:name', 'use_parent' => '1']));
    }

    public function testEmptyValueFallsBackToTheOtherProduct(): void
    {
        $this->values = ['child' => [], 'parent' => ['description' => 'From parent']];

        $this->assertSame(['From parent'], $this->resolve(['source' => 'attribute:description']));
    }

    public function testFallbackTextIsUsedWhenNothingResolves(): void
    {
        $this->assertSame(['Unbranded'], $this->resolve(['source' => 'attribute:brand', 'fallback' => 'Unbranded']));
        $this->assertSame([], $this->resolve(['source' => 'attribute:brand']));
    }

    public function testStaticSourceUsesTheRowValue(): void
    {
        $this->assertSame(['new'], $this->resolve(['source' => 'static', 'value' => ' new ']));
    }

    public function testTemplateReplacesTokensAndTidiesEmptyOnes(): void
    {
        $this->values = ['child' => ['name' => 'Tee', 'color' => 'Blue'], 'parent' => []];

        $this->assertSame(
            ['Tee - Blue'],
            $this->resolve(['source' => 'template', 'value' => '{{name}} - {{ color }}'])
        );
        $this->assertSame(['Tee'], $this->resolve(['source' => 'template', 'value' => '{{name}} - {{size}}']));
    }

    public function testResolverSourceMayReturnSeveralValues(): void
    {
        $this->assertCount(2, $this->resolve(['source' => 'resolver:images']));
        $this->assertSame([], $this->resolve(['source' => 'resolver:unknown']));
    }

    public function testCollectsAttributeCodesFromAttributeAndTemplateRows(): void
    {
        $codes = $this->valueResolver->getAttributeCodes([
            ['source' => 'attribute:name'],
            ['source' => 'template', 'value' => '{{name}} {{color}}'],
            ['source' => 'resolver:images'],
            ['source' => 'static', 'value' => '{{ignored}}'],
        ]);

        $this->assertSame(['name', 'color'], $codes);
    }

    /**
     * @param array $row
     * @return string[]
     */
    private function resolve(array $row): array
    {
        $context = new ProductContext(
            $this->product,
            $this->parent,
            $this->createMock(StoreInterface::class),
            $this->createMock(Feed::class),
            true
        );

        return $this->valueResolver->resolve($row, $context);
    }
}
