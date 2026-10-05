<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Click;

/**
 * Validates the click cookie written by the storefront script.
 *
 * The cookie comes from the browser, so nothing in it is trusted: identifiers must look like Google click IDs and
 * every other field is bounded before it reaches the database.
 */
class CookieParser
{
    public const COOKIE_NAME = 'upturnstudio_gf_click';

    private const ID_FIELDS = ['gclid', 'gbraid', 'wbraid'];
    private const ID_PATTERN = '/^[A-Za-z0-9_\-.]{1,255}$/';
    private const MAX_SKU_LENGTH = 64;
    private const MAX_URL_LENGTH = 2000;
    private const MAX_AGE_SECONDS = 400 * 86400;

    /**
     * Parse the raw cookie value.
     *
     * @param string|null $raw
     * @param int|null $now Current Unix time, for tests
     * @return array|null Click data, or null when the cookie holds no usable click ID
     */
    public function parse(?string $raw, ?int $now = null): ?array
    {
        if ($raw === null || $raw === '' || strlen($raw) > 8192) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }

        $click = [];
        foreach (self::ID_FIELDS as $field) {
            $value = $data[$field] ?? null;
            $click[$field] = is_string($value) && preg_match(self::ID_PATTERN, $value) ? $value : null;
        }
        if (!array_filter($click)) {
            return null;
        }

        $click['landing_sku'] = $this->cleanSku($data['sku'] ?? null);
        $click['landing_url'] = $this->cleanUrl($data['url'] ?? null);
        $click['clicked_at'] = $this->cleanTimestamp($data['ts'] ?? null, $now ?? time());

        return $click;
    }

    /**
     * SKU without control characters, null when missing or too long.
     *
     * @param mixed $sku
     * @return string|null
     */
    private function cleanSku(mixed $sku): ?string
    {
        if (!is_string($sku)) {
            return null;
        }
        $sku = trim((string) preg_replace('/[\x00-\x1F\x7F]/', '', $sku));

        return $sku === '' || mb_strlen($sku) > self::MAX_SKU_LENGTH ? null : $sku;
    }

    /**
     * HTTP(S) URL truncated to a storable length, null otherwise.
     *
     * @param mixed $url
     * @return string|null
     */
    private function cleanUrl(mixed $url): ?string
    {
        if (!is_string($url) || !preg_match('#^https?://#i', $url)) {
            return null;
        }

        return mb_substr((string) preg_replace('/[\x00-\x1F\x7F]/', '', $url), 0, self::MAX_URL_LENGTH);
    }

    /**
     * Click time as a UTC datetime, null when it is missing, in the future or implausibly old.
     *
     * @param mixed $milliseconds
     * @param int $now
     * @return string|null
     */
    private function cleanTimestamp(mixed $milliseconds, int $now): ?string
    {
        if (!is_int($milliseconds) && !is_float($milliseconds)) {
            return null;
        }
        $seconds = (int) ($milliseconds / 1000);
        if ($seconds > $now + 86400 || $seconds < $now - self::MAX_AGE_SECONDS) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $seconds);
    }
}
