<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Test\Unit\Model\Feed;

use PHPUnit\Framework\TestCase;
use UpturnStudio\GoogleFeed\Model\Feed\ValueSanitizer;

/**
 * @covers \UpturnStudio\GoogleFeed\Model\Feed\ValueSanitizer
 */
class ValueSanitizerTest extends TestCase
{
    public function testStripsMarkupAndKeepsParagraphsApart(): void
    {
        $sanitizer = new ValueSanitizer();

        $this->assertSame(
            'First line. Second & third.',
            $sanitizer->clean("<p>First line.</p><p>Second &amp; third.</p><script>alert(1)</script>\n\n")
        );
    }

    public function testRemovesCharactersXmlCannotCarry(): void
    {
        $this->assertSame('ab', (new ValueSanitizer())->clean("a\x00\x0Bb"));
    }

    public function testTruncatesOnCharactersNotBytes(): void
    {
        $this->assertSame('ééé', (new ValueSanitizer())->clean('ééééé', 3));
    }
}
