<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

/**
 * The Google product attributes offered in the mapping editor.
 *
 * Populated through di.xml, so other modules can add attributes without code changes. Each entry has a label and
 * may have a default source ("attribute:<code>", "resolver:<code>" or "static") with a default value.
 */
class GoogleAttributePool
{
    public const CODE_PATTERN = '/^[a-z][a-z0-9_]{0,63}$/';

    /**
     * @param array[] $attributes
     */
    public function __construct(
        private readonly array $attributes = []
    ) {
    }

    /**
     * Attribute definitions keyed by Google attribute code.
     *
     * @return array[]
     */
    public function getAll(): array
    {
        return $this->attributes;
    }

    /**
     * Whether the code is one of the configured attributes.
     *
     * @param string $code
     * @return bool
     */
    public function has(string $code): bool
    {
        return isset($this->attributes[$code]);
    }

    /**
     * Whether the code is well formed enough to be written as a feed element.
     *
     * @param string $code
     * @return bool
     */
    public function isValidCode(string $code): bool
    {
        return (bool) preg_match(self::CODE_PATTERN, $code);
    }
}
