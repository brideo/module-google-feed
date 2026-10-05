<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Mcp;

use UpturnStudio\Mcp\Api\ToolExecutionException;
use UpturnStudio\Mcp\Api\ToolInterface;

/**
 * Base for the Google Feeds MCP tools: one tool per action.
 */
abstract class AbstractTool implements ToolInterface
{
    protected const ACL_FEEDS = 'UpturnStudio_GoogleFeed::api_feeds';
    protected const ACL_FEEDS_MANAGE = 'UpturnStudio_GoogleFeed::api_feeds_manage';
    protected const ACL_ATTRIBUTES = 'UpturnStudio_GoogleFeed::api_attributes';
    protected const ACL_ORDERS = 'UpturnStudio_GoogleFeed::api_orders';
    protected const ACL_SETTINGS = 'UpturnStudio_GoogleFeed::api_settings';

    /**
     * @param ToolGuard $guard
     */
    public function __construct(
        private readonly ToolGuard $guard
    ) {
    }

    /**
     * What the tool does, written for the model that will choose it.
     *
     * @return string
     */
    abstract protected function description(): string;

    /**
     * JSON schema of the tool's arguments.
     *
     * @return array
     */
    abstract protected function inputSchema(): array;

    /**
     * ACL resource the admin must hold.
     *
     * @return string
     */
    abstract protected function aclResource(): string;

    /**
     * Whether the tool changes data. Such tools are off until an admin switches them on.
     *
     * @return bool
     */
    abstract protected function changesData(): bool;

    /**
     * Do the work.
     *
     * @param array $arguments
     * @return array
     */
    abstract protected function run(array $arguments): array;

    /**
     * @inheritdoc
     */
    public function getDefinition(): array
    {
        $description = $this->description();
        if ($this->changesData()) {
            $description .= ' Changes data, and only works when an admin has switched on Google Feeds edit tools.';
        }

        return ['description' => $description, 'inputSchema' => $this->inputSchema()];
    }

    /**
     * @inheritdoc
     */
    public function execute(int $adminUserId, array $arguments): array
    {
        return $this->guard->run(
            $adminUserId,
            $this->aclResource(),
            $this->changesData(),
            fn (): array => $this->run($arguments)
        );
    }

    /**
     * An object schema.
     *
     * @param array $properties
     * @param string[] $required
     * @return array
     */
    protected function object(array $properties, array $required = []): array
    {
        $schema = ['type' => 'object', 'properties' => $properties ?: (object) []];
        if ($required) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * The schema of one mapping row.
     *
     * @return array
     */
    protected function mappingRowSchema(): array
    {
        return $this->object([
            'google_attribute' => ['type' => 'string', 'description' => 'Google attribute code, e.g. title or brand.'],
            'source' => [
                'type' => 'string',
                'description' => 'static, template, resolver:<code> or attribute:<code>. '
                    . 'List the valid ones with google_feed_get_options.',
            ],
            'value' => ['type' => 'string', 'description' => 'Text for static and template sources; {{code}} fills in attributes.'],
            'fallback' => ['type' => 'string', 'description' => 'Text written when the product has no value.'],
            'use_parent' => ['type' => 'boolean', 'description' => 'For variants, read the configurable product first.'],
        ], ['google_attribute', 'source']);
    }

    /**
     * The schema of feed fields, shared by create and update.
     *
     * @return array
     */
    protected function feedFieldsSchema(): array
    {
        return [
            'name' => ['type' => 'string'],
            'store_id' => ['type' => 'integer', 'description' => 'Store view ID; see google_feed_get_options.'],
            'is_active' => ['type' => 'boolean', 'description' => 'Whether the feed is generated on its schedule.'],
            'target_country' => ['type' => 'string', 'description' => 'Two-letter country code, e.g. US.'],
            'price_tax' => ['type' => 'string', 'enum' => ['auto', 'incl', 'excl']],
            'cron_expression' => ['type' => 'string', 'description' => 'Schedule in the admin timezone, e.g. "0 2 * * *"; empty for manual only.'],
            'filters' => $this->object([
                'category_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                'product_types' => ['type' => 'array', 'items' => ['type' => 'string']],
                'visibility' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => '2 catalog, 3 search, 4 both.'],
                'attribute_set_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                'exclude_out_of_stock' => ['type' => 'boolean'],
            ]),
            'mapping' => [
                'type' => 'array',
                'items' => $this->mappingRowSchema(),
                'description' => 'The whole mapping. Use google_feed_set_mapping_rows to change single rows.',
            ],
        ];
    }

    /**
     * A required integer argument.
     *
     * @param array $arguments
     * @param string $name
     * @return int
     * @throws ToolExecutionException
     */
    protected function intArgument(array $arguments, string $name): int
    {
        $value = $arguments[$name] ?? null;
        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            throw new ToolExecutionException(sprintf('"%s" is required and must be a whole number.', $name));
        }

        return (int) $value;
    }

    /**
     * A required string argument.
     *
     * @param array $arguments
     * @param string $name
     * @return string
     * @throws ToolExecutionException
     */
    protected function stringArgument(array $arguments, string $name): string
    {
        $value = $arguments[$name] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new ToolExecutionException(sprintf('"%s" is required.', $name));
        }

        return trim($value);
    }
}
