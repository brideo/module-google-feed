<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Test\Unit\Model\Click;

use PHPUnit\Framework\TestCase;
use UpturnStudio\GoogleFeed\Model\Click\CookieParser;

/**
 * @covers \UpturnStudio\GoogleFeed\Model\Click\CookieParser
 */
class CookieParserTest extends TestCase
{
    private const NOW = 1791230000;

    public function testParsesAValidClick(): void
    {
        $click = (new CookieParser())->parse(json_encode([
            'gclid' => 'Cj0KCQ_abc-123',
            'ts' => (self::NOW - 3600) * 1000,
            'url' => 'https://example.test/bag.html',
            'sku' => '24-MB01',
        ]), self::NOW);

        $this->assertSame('Cj0KCQ_abc-123', $click['gclid']);
        $this->assertNull($click['gbraid']);
        $this->assertNull($click['wbraid']);
        $this->assertSame('24-MB01', $click['landing_sku']);
        $this->assertSame('https://example.test/bag.html', $click['landing_url']);
        $this->assertSame(gmdate('Y-m-d H:i:s', self::NOW - 3600), $click['clicked_at']);
    }

    public function testRejectsCookiesWithoutAUsableClickId(): void
    {
        $parser = new CookieParser();

        $this->assertNull($parser->parse(null));
        $this->assertNull($parser->parse('not json'));
        $this->assertNull($parser->parse('"a string"'));
        $this->assertNull($parser->parse(json_encode(['sku' => '24-MB01'])));
        $this->assertNull($parser->parse(json_encode(['gclid' => "abc'; DROP TABLE x"])));
        $this->assertNull($parser->parse(json_encode(['gclid' => ['nested']])));
        $this->assertNull($parser->parse(json_encode(['gclid' => str_repeat('a', 256)])));
    }

    public function testDropsUntrustworthyOptionalFieldsButKeepsTheClick(): void
    {
        $click = (new CookieParser())->parse(json_encode([
            'wbraid' => 'wb-1',
            'ts' => (self::NOW + 10 * 86400) * 1000,
            'url' => 'javascript:alert(1)',
            'sku' => str_repeat('x', 65),
        ]), self::NOW);

        $this->assertSame('wb-1', $click['wbraid']);
        $this->assertNull($click['clicked_at']);
        $this->assertNull($click['landing_url']);
        $this->assertNull($click['landing_sku']);
    }

    public function testTruncatesLongUrls(): void
    {
        $click = (new CookieParser())->parse(json_encode([
            'gbraid' => 'gb-1',
            'url' => 'https://example.test/' . str_repeat('a', 3000),
        ]), self::NOW);

        $this->assertSame(2000, mb_strlen($click['landing_url']));
    }
}
