<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

/**
 * Turns catalogue text (often HTML) into plain text that is safe in an XML feed.
 */
class ValueSanitizer
{
    /**
     * Strip markup, control characters and repeated whitespace, then optionally truncate.
     *
     * @param string $value
     * @param int|null $maxLength
     * @return string
     */
    public function clean(string $value, ?int $maxLength = null): string
    {
        // Separate block-level content before the tags go, so "<p>a</p><p>b</p>" does not become "ab".
        $value = (string) preg_replace('#<(br|/p|/div|/li|/h[1-6])\b[^>]*>#i', '$0 ', $value);
        $value = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $value);
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Characters that are not allowed in XML 1.0.
        $value = (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value);
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        if ($maxLength !== null && mb_strlen($value) > $maxLength) {
            $value = rtrim(mb_substr($value, 0, $maxLength));
        }

        return $value;
    }
}
