# Getting started

This guide takes you from installing the module to a feed that Merchant Center
fetches every day.

## Before you start

- Magento cron must be running. Schedules and the **Generate Now** button both
  depend on it.
- Inventory Management (MSI) must be enabled. Availability comes from the
  salable quantity of the stock assigned to the feed's website.
- You need a Google Merchant Center account for the last step.

## 1. Install

```bash
composer require upturnstudio/module-google-feed
bin/magento module:enable UpturnStudio_GoogleFeed
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## 2. Create your first feed

1. Go to **Marketing > SEO & Search > Google Feeds**.
2. Click **Add Feed**.
3. Enter a **Feed Name** and choose the **Store View**. The store view decides
   the language, currency, prices and stock the feed lists.
4. Leave everything else as it is and click **Save and Continue Edit**.

A new feed already has a working attribute mapping, so you can generate it
straight away and refine it later.

![The feed form](images/feed-form-general.jpg)

## 3. Generate it

Click **Generate Now** and confirm. The feed is queued and written on the next
cron run, usually within a minute or two.

To generate it immediately instead, run this on the server:

```bash
bin/magento upturnstudio:google-feed:generate
```

Go back to the grid. When **Last Run** shows **Success**, the **Items** column
shows how many products were written and the **Feed URL** column shows where
the file is.

![The feed grid after a successful run](images/feed-grid.jpg)

## 4. Connect Merchant Center

1. Copy the **Feed URL** from the grid.
2. In Merchant Center, add a product data source and choose to add products
   from a file with a link (a scheduled fetch).
3. Paste the URL and set a fetch time an hour or so after the feed's own
   schedule. The default schedule generates at 02:00, so 03:00 or later works.
4. Choose the same country and language as the feed's store view.

The URL contains a random token and needs no username or password.

## 5. Check the result

After the first fetch, open the data source in Merchant Center and look at the
product issues it reports. The most common ones on a new feed are missing GTINs
and missing brands. [Attribute mapping](attribute-mapping.md) explains how to
fill them.

## Next steps

- [Choose which products go in the feed](creating-a-feed.md)
- [Adjust the attribute mapping](attribute-mapping.md)
- [Turn on ad click tracking](ad-click-tracking.md) (it is on by default)
- [Connect the reporting system](subscription-and-api-access.md)
