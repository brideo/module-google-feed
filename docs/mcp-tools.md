# AI connector tools

The module adds one tool per action to the [AI connector](../../Mcp/README.md)
(`UpturnStudio_Mcp`), which is a required dependency. Any MCP client connected to
the store, such as Claude or the reporting service, sees them in `tools/list`.

Feed management is done through these tools. There is no REST or GraphQL API for
feeds, mapping or settings.

## Who can call a tool

Every call needs both of these:

1. **The admin's own role must hold the tool's permission** (below). The connector
   itself only checks the single "AI Connector (MCP)" permission, so each tool
   checks its own. A tool refused for permission says which one is missing.
2. **For tools that change data: an admin must have switched them on.** Stores >
   Configuration > UpturnStudio > Google Feeds > AI Connector (MCP) > Allow Tools
   That Change Data. It is **off by default**. Read tools work without it.

The switch is deliberately not a setting any tool can change.

> The connector's sign-in screen tells admins it grants **read-only** access.
> With the switch on, that is no longer true for Google Feeds data. Turn it on
> only if everyone who can connect understands that.

## Tools

Read tools (never change anything):

| Tool | What it does | Permission |
|---|---|---|
| `google_feed_get_options` | Lists the Google attributes, mapping sources, product types, visibilities, price tax modes, store views and the default mapping. Call this first when creating or editing a feed. | Read Feeds, Options and Item Previews |
| `google_feed_list_feeds` | Lists all feeds with store view, schedule, last run status and URL. | Read Feeds, Options and Item Previews |
| `google_feed_get_feed` | One feed in full: filters, mapping, schedule, URL, and the status and error of its last run. | Read Feeds, Options and Item Previews |
| `google_feed_preview_item` | Shows exactly what a SKU would look like in a feed, or why it would not be listed. Optionally takes a mapping that is not saved, to check a change before making it. | Read Feeds, Options and Item Previews |
| `google_feed_list_attribute_values` | Lists Google attribute values stored per SKU. | Read and Write Google Attribute Values |
| `google_feed_list_attributed_orders` | Lists orders that came from a Google Ads click, with click ID, landing SKU and purchased items. | Read Click-Attributed Orders |
| `google_feed_get_settings` | Click capture and generation settings. | Read and Change Settings |

Tools that change data (need the switch):

| Tool | What it does | Permission |
|---|---|---|
| `google_feed_create_feed` | Creates a feed. `name` and `store_id` are required; everything else has a default, including the standard mapping. Not scheduled until `is_active` is true. | Create, Change and Delete Feeds; Run Generation |
| `google_feed_update_feed` | Changes only the fields given. Giving `filters` changes just the filter fields given. Giving `mapping` replaces the whole mapping. | same |
| `google_feed_delete_feed` | Deletes a feed and its generated file. | same |
| `google_feed_duplicate_feed` | Copies a feed into a new, unscheduled one. | same |
| `google_feed_generate_feed` | Queues a generation run; read the feed to follow `last_status`. | same |
| `google_feed_set_mapping_rows` | Adds or replaces mapping rows by Google attribute, leaving the others. | same |
| `google_feed_remove_mapping_rows` | Removes mapping rows by Google attribute. | same |
| `google_feed_set_attribute_values` | Creates or replaces Google attribute values per SKU, at most 1000 per call. Invalid items are reported and skipped. | Read and Write Google Attribute Values |
| `google_feed_delete_attribute_values` | Removes stored values, so the feed mapping applies again. | same |
| `google_feed_update_settings` | Changes click capture and generation settings. Without `store_id` the default changes; `batch_size` is global. | Read and Change Settings |

The subscription key and the switch above cannot be changed by any tool.

## Results and errors

A successful call returns the result as structured JSON, as in the examples below.
A refusal or failure comes back as a tool error (`isError: true`) with a message
a person can read. Validation problems are all reported at once:

```
A feed name is required. store_id 9 is not a store view. "nope" is not a valid cron expression.
```

## Suggested workflow for an optimiser

1. `google_feed_get_options` once, to learn what is valid.
2. `google_feed_get_feed` to read the current mapping.
3. `google_feed_preview_item` with a candidate `mapping` for a few SKUs, to see
   the effect without saving anything.
4. `google_feed_set_mapping_rows` to apply it.
5. `google_feed_generate_feed`, then `google_feed_get_feed` until `last_status`
   is `success`.
6. `google_feed_set_attribute_values` for per-product changes such as custom
   labels, improved titles or a Google category.

## Example

Previewing a change to one title before applying it:

```json
{
  "name": "google_feed_preview_item",
  "arguments": {
    "feed_id": 1,
    "sku": "WS12-M-Orange",
    "mapping": [
      {"google_attribute": "id", "source": "attribute:sku"},
      {"google_attribute": "link", "source": "resolver:url"},
      {"google_attribute": "title", "source": "template", "value": "{{name}} | Free delivery"}
    ]
  }
}
```

returns

```json
{
  "feed_id": 1,
  "sku": "WS12-M-Orange",
  "listed": true,
  "reason": null,
  "attributes": [
    {"code": "id", "values": ["WS12-M-Orange"]},
    {"code": "link", "values": ["https://example.com/radiant-tee.html"]},
    {"code": "title", "values": ["Radiant Tee-M-Orange | Free delivery"]},
    {"code": "identifier_exists", "values": ["no"]}
  ]
}
```

## Adding your own tools

The connector's tool registry is open to any module. See "Adding more tools" in
the [connector's README](../../Mcp/README.md). The tools here follow that
pattern: each implements `UpturnStudio\Mcp\Api\ToolInterface`, extends
`UpturnStudio\GoogleFeed\Model\Mcp\AbstractTool`, and is registered in this
module's `etc/di.xml`. `AbstractTool` applies the permission check and the
"changes data" switch, so a new tool only declares its schema, permission and
body.
