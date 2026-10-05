<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Test\Unit\Model\Feed\Xml;

use PHPUnit\Framework\TestCase;
use UpturnStudio\GoogleFeed\Model\Feed\Xml\ItemWriter;
use XMLWriter;

/**
 * @covers \UpturnStudio\GoogleFeed\Model\Feed\Xml\ItemWriter
 */
class ItemWriterTest extends TestCase
{
    public function testWritesWellFormedNamespacedXmlAndEscapesValues(): void
    {
        $writer = new ItemWriter();
        $xml = new XMLWriter();
        $xml->openMemory();

        $writer->startFeed($xml, 'Tom & Jerry <Feed>', 'https://example.test/', 'Store');
        $writer->writeItem($xml, [
            'id' => ['SKU-1'],
            'title' => ["Salt & Pepper <Set> \x0B\"Deluxe\""],
            'additional_image_link' => ['https://example.test/1.jpg?a=1&b=2', 'https://example.test/2.jpg'],
        ]);
        $writer->endFeed($xml);

        $document = new \DOMDocument();
        $this->assertTrue($document->loadXML($xml->outputMemory()), 'Feed must be well-formed XML.');

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('g', ItemWriter::GOOGLE_NAMESPACE);

        $this->assertSame('2.0', $document->documentElement->getAttribute('version'));
        $this->assertSame('Tom & Jerry <Feed>', $xpath->evaluate('string(/rss/channel/title)'));
        $this->assertSame('SKU-1', $xpath->evaluate('string(/rss/channel/item/g:id)'));
        $this->assertSame('Salt & Pepper <Set> "Deluxe"', $xpath->evaluate('string(/rss/channel/item/g:title)'));
        $this->assertSame(2, $xpath->query('/rss/channel/item/g:additional_image_link')->length);
        $this->assertSame(
            'https://example.test/1.jpg?a=1&b=2',
            $xpath->evaluate('string(/rss/channel/item/g:additional_image_link[1])')
        );
    }
}
