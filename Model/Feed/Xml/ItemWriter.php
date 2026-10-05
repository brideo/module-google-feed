<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Xml;

use XMLWriter;

/**
 * Writes the RSS 2.0 envelope and items of a Google product feed.
 */
class ItemWriter
{
    public const GOOGLE_NAMESPACE = 'http://base.google.com/ns/1.0';

    private const INVALID_XML_CHARS = '/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u';

    /**
     * Open the document and channel.
     *
     * @param XMLWriter $xml
     * @param string $title
     * @param string $link
     * @param string $description
     * @return void
     */
    public function startFeed(XMLWriter $xml, string $title, string $link, string $description): void
    {
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:g', self::GOOGLE_NAMESPACE);
        $xml->startElement('channel');
        $xml->writeElement('title', $this->strip($title));
        $xml->writeElement('link', $this->strip($link));
        $xml->writeElement('description', $this->strip($description));
    }

    /**
     * Write one item; attributes with several values repeat their element.
     *
     * @param XMLWriter $xml
     * @param string[][] $item Google attribute code => values
     * @return void
     */
    public function writeItem(XMLWriter $xml, array $item): void
    {
        $xml->startElement('item');
        foreach ($item as $code => $values) {
            foreach ($values as $value) {
                $xml->writeElement('g:' . $code, $this->strip((string) $value));
            }
        }
        $xml->endElement();
    }

    /**
     * Close the channel and document.
     *
     * @param XMLWriter $xml
     * @return void
     */
    public function endFeed(XMLWriter $xml): void
    {
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();
    }

    /**
     * Remove characters XML 1.0 cannot carry; XMLWriter escapes the rest.
     *
     * @param string $value
     * @return string
     */
    private function strip(string $value): string
    {
        return (string) preg_replace(self::INVALID_XML_CHARS, '', $value);
    }
}
