<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

use UpturnStudio\GoogleFeed\Model\Feed\Resolver\ResolverPool;

/**
 * Turns one mapping row into the values written for a feed item.
 *
 * A row's source is "attribute:<code>", "resolver:<code>", "static" or "template". Static and template sources
 * take their text from the row's value; templates replace {{attribute_code}} tokens.
 */
class ValueResolver
{
    public const SOURCE_ATTRIBUTE = 'attribute';
    public const SOURCE_RESOLVER = 'resolver';
    public const SOURCE_STATIC = 'static';
    public const SOURCE_TEMPLATE = 'template';

    private const TOKEN_PATTERN = '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/';

    /**
     * @param ResolverPool $resolverPool
     * @param AttributeValueReader $attributeValueReader
     * @param ValueSanitizer $sanitizer
     */
    public function __construct(
        private readonly ResolverPool $resolverPool,
        private readonly AttributeValueReader $attributeValueReader,
        private readonly ValueSanitizer $sanitizer
    ) {
    }

    /**
     * Values for the row; an empty array when the product has nothing to write.
     *
     * @param array $row
     * @param ProductContext $context
     * @return string[]
     */
    public function resolve(array $row, ProductContext $context): array
    {
        [$type, $code] = $this->parseSource((string) ($row['source'] ?? ''));
        $useParent = !empty($row['use_parent']);
        $text = (string) ($row['value'] ?? '');

        $values = match ($type) {
            self::SOURCE_RESOLVER => $this->resolverPool->get($code)?->resolve($context) ?? [],
            self::SOURCE_ATTRIBUTE => $this->wrap($this->readAttribute($code, $context, $useParent)),
            self::SOURCE_STATIC => $this->wrap(trim($text)),
            self::SOURCE_TEMPLATE => $this->wrap($this->renderTemplate($text, $context, $useParent)),
            default => [],
        };

        $fallback = trim((string) ($row['fallback'] ?? ''));
        if (!$values && $fallback !== '') {
            $values = [$fallback];
        }

        return $values;
    }

    /**
     * Product attribute codes the mapping reads, so only those are loaded.
     *
     * @param array[] $mapping
     * @return string[]
     */
    public function getAttributeCodes(array $mapping): array
    {
        $codes = [];
        foreach ($mapping as $row) {
            [$type, $code] = $this->parseSource((string) ($row['source'] ?? ''));
            if ($type === self::SOURCE_ATTRIBUTE && $code !== '') {
                $codes[] = $code;
            } elseif ($type === self::SOURCE_TEMPLATE
                && preg_match_all(self::TOKEN_PATTERN, (string) ($row['value'] ?? ''), $matches)
            ) {
                array_push($codes, ...$matches[1]);
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Whether any row uses the given built-in resolver.
     *
     * @param array[] $mapping
     * @param string $resolverCode
     * @return bool
     */
    public function usesResolver(array $mapping, string $resolverCode): bool
    {
        foreach ($mapping as $row) {
            if (($row['source'] ?? '') === self::SOURCE_RESOLVER . ':' . $resolverCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * Split a source string into its type and code.
     *
     * @param string $source
     * @return string[]
     */
    private function parseSource(string $source): array
    {
        $parts = explode(':', $source, 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    /**
     * Read an attribute from the product or its parent, falling back to the other when empty.
     *
     * @param string $code
     * @param ProductContext $context
     * @param bool $useParent
     * @return string
     */
    private function readAttribute(string $code, ProductContext $context, bool $useParent): string
    {
        if ($code === '') {
            return '';
        }
        $candidates = $useParent ? [$context->parent, $context->product] : [$context->product, $context->parent];
        foreach (array_filter($candidates) as $product) {
            $value = $this->sanitizer->clean($this->attributeValueReader->read($product, $code));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * Replace {{code}} tokens and tidy separators left behind by empty ones.
     *
     * @param string $template
     * @param ProductContext $context
     * @param bool $useParent
     * @return string
     */
    private function renderTemplate(string $template, ProductContext $context, bool $useParent): string
    {
        $rendered = (string) preg_replace_callback(
            self::TOKEN_PATTERN,
            fn (array $match): string => $this->readAttribute($match[1], $context, $useParent),
            $template
        );

        return trim($this->sanitizer->clean($rendered), " \t-–|,/");
    }

    /**
     * Wrap a non-empty string as a single-value list.
     *
     * @param string $value
     * @return string[]
     */
    private function wrap(string $value): array
    {
        return $value === '' ? [] : [$value];
    }
}
