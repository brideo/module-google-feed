# REST API for the reporting system

Feeds, mapping, discovery, previews, generation and settings are managed through
the [AI connector tools](mcp-tools.md), not through REST or GraphQL. This page
covers the REST routes that remain: Google attribute values, click-attributed
orders, and the headless cart click.

This is the contract between the module and the external system that holds the
Google Ads connection, the subscription, and the reporting and MCP layer.

## How the pieces fit

| Data | Source | Key |
|---|---|---|
| Spend and clicks per product | Google Ads API, `shopping_performance_view` with `segments.product_item_id` | Item ID |
| Sales, refunds and cost per product | `GET attributed-orders` on this module | SKU |
| Which store and currency an item ID belongs to | `google_feed_list_feeds` MCP tool | Feed |

The feed's `id` is mapped to the SKU by default, so the Google Ads item ID and
the order item SKU are the same value. Google lower-cases item IDs in reports,
so compare them case-insensitively. If a merchant maps `id` to something other
than SKU, the join no longer holds.

Two ways to attribute revenue are available from the same data:

- **By purchased SKU**: sum `items[].row_total` per `sku`. Answers "what did
  ad-driven orders buy".
- **By landing SKU**: sum order totals per `landing_sku`. Answers "what did the
  spend on this product's ads bring in", including orders for other products.

Later option: the captured `gclid` values can be uploaded to Google Ads as
offline conversions, with the real order value.

## Authentication

Create a Magento Integration (System > Extensions > Integrations) with the
resources under **Google Feeds > API**, activate it, and send its access token:

```
Authorization: Bearer <access token>
```

Magento must allow integration tokens as bearer tokens (Stores > Configuration
> Services > OAuth > Consumer Settings > Allow OAuth Access Tokens to be used
as standalone Bearer tokens), or use OAuth 1.0a signing.

| Status | Meaning |
|---|---|
| 401 | Missing or invalid token, or the token lacks the resource |
| 400 | Invalid input; the message says what |

All times are UTC.

Base path: `/rest/V1/upturnstudio/google-feed` (prefix the store code, e.g.
`/rest/default/V1/...`, as with any Magento REST call).

## PUT /product-attributes

Creates or replaces Google attribute values. At most 1000 items per request.

```json
{
  "items": [
    {"sku": "24-MB01", "attribute_code": "google_product_category", "value": "Apparel & Accessories > Handbags, Wallets & Cases"},
    {"sku": "24-MB01", "attribute_code": "gtin", "value": "00012345678905", "source": "catalogue-sync"},
    {"sku": "24-MB01", "store_id": 2, "attribute_code": "title", "value": "Joust Sporttasche"},
    {"sku": "24-MB04", "attribute_code": "custom_label_0", "value": "low-roas"}
  ]
}
```

| Field | Notes |
|---|---|
| `sku` | Required, up to 64 characters. The product does not have to exist yet. |
| `attribute_code` | Required. Lower-case letters, digits and underscores. Any Google attribute is accepted, not only those in the mapping editor. |
| `value` | Up to 10000 characters. An empty value removes the attribute from the feed item, even when the mapping would fill it. |
| `store_id` | Optional. Omitted or 0 applies to every store; a store view ID overrides that for one store. |
| `source` | Optional label, up to 32 characters. Defaults to `api`. |

For `additional_image_link`, separate several URLs with commas.

Invalid items are skipped and reported; valid items in the same request are
still written:

```json
{
  "processed": 4,
  "errors": ["Item 5: store_id 99 does not exist."]
}
```

Values take effect the next time the feed is generated.

## GET /product-attributes

Query parameters, all optional: `sku`, `storeId`, `updatedFrom`, `pageSize`
(default 200, max 1000), `currentPage`.

```json
{
  "items": [
    {
      "sku": "24-MB01",
      "store_id": 0,
      "attribute_code": "gtin",
      "value": "00012345678905",
      "source": "catalogue-sync",
      "updated_at": "2026-10-05 20:53:08"
    }
  ],
  "total_count": 1
}
```

## POST /product-attributes/delete

Removes stored values, so the feed mapping applies again. Magento does not
read a body on `DELETE`, which is why this is a `POST`.

```json
{"items": [{"sku": "24-MB01", "attribute_code": "gtin"}]}
```

Response: `{"processed": 1, "errors": []}`, where `processed` is the number of
rows removed.

## GET /attributed-orders

Orders that carry a captured click, oldest update first.

Query parameters: `updatedFrom` (ISO 8601 or `Y-m-d H:i:s`, read as UTC when no
timezone is given), `pageSize` (default 100, max 500), `currentPage`.

To sync incrementally, pass the newest `updated_at` already seen as
`updatedFrom` and page until a page comes back empty. The filter is inclusive,
so the last order of the previous run is returned again; upsert by `order_id`.
An order reappears whenever it changes, for example when it is refunded.

```json
{
  "items": [
    {
      "order_id": 3,
      "increment_id": "000000003",
      "store_id": 1,
      "state": "new",
      "status": "pending",
      "created_at": "2026-10-05 20:54:00",
      "updated_at": "2026-10-05 20:54:02",
      "order_currency_code": "USD",
      "base_currency_code": "USD",
      "subtotal": 64,
      "shipping_amount": 10,
      "discount_amount": 0,
      "grand_total": 74,
      "base_grand_total": 74,
      "total_refunded": 0,
      "gclid": "Cj0KCQ...",
      "landing_sku": "24-MB01",
      "landing_url": "https://example.com/joust-duffle-bag.html",
      "clicked_at": "2026-10-05 20:53:57",
      "items": [
        {
          "sku": "24-MB04",
          "name": "Strive Shoulder Pack",
          "product_id": 2,
          "product_type": "simple",
          "qty_ordered": 2,
          "qty_refunded": 0,
          "price": 32,
          "row_total": 64,
          "row_total_incl_tax": 64,
          "discount_amount": 0,
          "tax_amount": 0,
          "amount_refunded": 0,
          "base_row_total": 64
        }
      ]
    }
  ],
  "total_count": 1
}
```

Notes:

- Fields without a value are omitted (`gbraid`, `wbraid`, `landing_sku`,
  `base_cost` and so on).
- For a configurable product, `sku` is the variant that was bought, which is
  the same value as the feed item ID.
- `base_cost` is the unit cost in the base currency, present only when the
  product has a cost.
- `landing_url` is the page path without its query string.
- Click data comes from the shopper's browser. It is validated for shape, but
  a `gclid` is only proven real when Google Ads recognises it.

## POST /guest-carts/:cartId/click and POST /carts/mine/click

For headless storefronts that cannot send Magento the click cookie. Attach the
click to the shopper's cart; when the cart becomes an order, the click is
recorded on it exactly as a cookie click would be. Needs no token for a guest
cart (knowing the masked cart ID is the credential) and a customer token for
`carts/mine`.

```json
{
  "click": {
    "gclid": "Cj0KCQ...",
    "landing_sku": "24-MB01",
    "landing_url": "https://shop.example/bag",
    "clicked_at": "2026-10-05T20:53:57Z"
  }
}
```

At least one of `gclid`, `gbraid` and `wbraid` is required, and each must look
like a Google click ID. A second call for the same cart replaces the first. A
cart that belongs to a customer is only found for that customer. If a cart has
a click attached, it is used instead of any cookie.

The same thing is available in GraphQL, with the same rules:

```graphql
mutation {
  setGoogleAdsClickOnCart(input: {
    cart_id: "<masked cart id>"
    gclid: "Cj0KCQ..."
    landing_sku: "24-MB01"
  })
}
```

## Subscription validation (to be built)

The module checks its key by POSTing JSON to the URL in
`upturnstudio_googlefeed/subscription/validation_url`:

```json
{"key": "<subscription key>", "domain": "https://example.com/"}
```

Expected answers:

| Response | Result |
|---|---|
| `200 {"valid": true}` | Active |
| `200 {"valid": false}`, or 401, 403, 404 | Not recognised: admins see a notice. Nothing in the module is switched off. |
| Anything else, or no answer | No change; the last answer for the same key stands |

The result is informational on the Magento side. The API on this page works
with or without a key; the reporting system decides which of its own features
a subscription unlocks.

Until the URL is configured, a key is stored but not verified.
