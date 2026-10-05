# Troubleshooting

## Feeds

### The feed stays on "Queued"

Scheduled runs and **Generate Now** need Magento cron.

- Check cron is installed and running: `crontab -l` for the Magento user should
  list a `bin/magento cron:run` entry (add it with `bin/magento cron:install`),
  and `var/log/cron.log` shows recent activity.
- If your server runs queue consumers separately from cron, make sure
  `upturnstudio_googlefeed.feed_generate` is one of them.
- To get the feed out now, run
  `bin/magento upturnstudio:google-feed:generate --feed-id=<ID>`.

### Last Run shows "Error"

Open **Columns** above the grid and tick **Last Error** to see the message.
The full detail is in `var/log/system.log` and `var/log/exception.log`, on
lines starting `UpturnStudio_GoogleFeed`. The previous file is still in place,
so Merchant Center keeps working from the older data.

### The Feed URL is empty or gives "not found"

The URL appears after the first successful run. If it is shown but does not
open, check that your web server serves `.xml` files from `pub/media`, and
that the file exists in `pub/media/upturnstudio/googlefeed/`.

### The feed has fewer products than expected

Work through these in order:

- **Filters.** Check Product Types, Categories, Attribute Sets and Visibility
  on the feed. An empty Product Types list means simple, virtual and
  downloadable only, so bundle and grouped products must be selected.
- **Status and website.** Only enabled products assigned to the feed's website
  are listed.
- **Visibility.** A product that is *Not Visible Individually* is only listed
  as a variant of an enabled, visible configurable product.
- **Leave Out Unavailable Products.** When on, out-of-stock products are
  skipped.
- **Configurable products** are not counted themselves. Their variants are.

### A product shows as out of stock but is in stock

Availability comes from the salable quantity of the stock assigned to the
feed's website, not the quantity on the product's default source. Check
**Product Salable Quantity** on the product, and that the website is assigned
to the right stock under **Stores > Inventory > Stocks**.

### Prices are wrong

- **Currency** is the store view's default display currency.
- **Tax** follows the feed's **Prices** setting. Google rejects prices that do
  not match the landing page, so match what shoppers see in that country.
- **Sale price** needs the catalogue price rule index to be up to date. Run
  `bin/magento indexer:reindex catalogrule_rule` if rule prices are missing.
- Prices are for shoppers who are not logged in. Customer group prices are not
  used.

### An attribute is missing from the feed

- The product may have no value. Add **If Empty, Use** text or tick **Prefer
  Parent** so variants take the parent's value.
- A stored empty value for that SKU removes the attribute on purpose. Check
  **Google Feed Attributes** on the product page.
- The row may be mapped to a product attribute that does not exist in this
  store.

### I changed the mapping but the feed looks the same

Save the feed, then generate it again. **Generate Now** uses the last saved
settings. Merchant Center also needs to fetch the new file.

### Merchant Center reports missing GTIN or brand

Map `gtin` and `brand` to the product attributes that hold them, or have them
pushed per SKU. Products that genuinely have no identifier are already marked
with `identifier_exists` set to `no`. See
[Attribute mapping](attribute-mapping.md#identifiers-gtin-mpn-and-brand).

### Generation runs out of memory or is slow

Lower **Batch Size** under **Stores > Configuration > UpturnStudio > Google
Feeds > Feed Generation**. Mapping fewer attributes and leaving out
`additional_image_link` also speeds up large catalogues.

## Click tracking

### Orders have no click data

- Check **Capture Google Click IDs** is on for that store view.
- Check auto-tagging is on in Google Ads, so ad links carry a `gclid`.
- With Cookie Restriction Mode on, the shopper must accept cookies.
- The order must be placed in the same browser as the click, within the cookie
  lifetime.
- Redirects that drop the query string lose the click ID. Test by opening a
  page with `?gclid=TEST123` and checking the address still shows it once the
  page has loaded.
- Flush the page cache after turning capture on.

### The landing SKU is empty

It is only recorded when the ad lands on a product page. Clicks that land on a
category page, the home page or a search page have a landing page but no SKU.

## AI connector

### A tool says "switched off"

Tools that change data are off by default. Turn on **Allow Tools That Change
Data** under **Stores > Configuration > UpturnStudio > Google Feeds > AI
Connector (MCP)**, after reading the note about the connector's read-only
sign-in screen in the [AI connector](ai-connector.md) guide. Read tools never
need it.

### A tool says the admin role does not include a permission

The connector acts as the admin who signed in. Add the permission named in the
message to that admin's role under **System > Permissions > User Roles > Role
Resources > Google Feeds > API and AI Connector**.

### The tools do not appear in the AI client

Check the `UpturnStudio_Mcp` module is enabled (`bin/magento module:status
UpturnStudio_Mcp`), that the admin's role has the **AI Connector (MCP)**
permission, and that the connector's public base URL is set for remote use.
Flush the cache after installing or upgrading.

### A preview says the product would not be listed

The preview's `reason` says why: the feed's filters, the product's status or
visibility, or being out of stock with unavailable products left out. It is the
same decision the generator makes, so the product is also missing from the real
feed.

## Reporting API

### The API returns 401

The access token is missing, wrong, or its integration lacks the resource for
that call. Check the integration is active, has the **Google Feeds > API**
resources ticked, and that standalone bearer tokens are allowed. See
[Subscription and API access](subscription-and-api-access.md).

### Pushed values do not appear in the feed

They take effect at the next generation. Check the value is stored by opening
the product and looking under **Google Feed Attributes**, then generate the
feed.

## Still stuck

Collect the feed ID, the **Last Error** text and the matching lines from
`var/log/system.log`, and pass them to your developer or support contact.
