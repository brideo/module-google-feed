# Generating feeds

A feed file is only as fresh as its last generation. Prices, stock and mapping
changes reach Merchant Center after the feed is generated again and Merchant
Center fetches it.

## Three ways to generate

### On a schedule

Turn on **Generate on Schedule** and set a **Schedule** on the feed. A
background job checks every minute and queues each feed whose time has come.

If cron was down when a feed was due, it is queued when cron comes back, as
long as that is within an hour. After a longer outage the feed waits for its
next scheduled time.

### Generate Now

On the feed form, or under **Select** on a grid row. The feed is queued and
written on the next cron run. **Last Run** shows **Queued**, then **Running**,
then **Success** or **Error**.

![Generate Now in the row actions](images/feed-grid-actions.jpg)

### From the command line

Runs immediately, without waiting for cron:

```bash
# Every feed with Generate on Schedule turned on
bin/magento upturnstudio:google-feed:generate

# One feed, whether scheduled or not
bin/magento upturnstudio:google-feed:generate --feed-id=3
```

The command prints the item count, the time taken and the feed URL.

## Reading the grid

![The feed grid](images/feed-grid.jpg)

| Column | Meaning |
|---|---|
| **Scheduled** | Whether **Generate on Schedule** is on. |
| **Schedule** | The feed's cron expression. |
| **Last Run** | *Queued*, *Running*, *Success* or *Error*. |
| **Items** | Products written by the last successful run. |
| **Last Generated** | When the last successful run finished. |
| **Feed URL** | The public address of the file. Empty until the first successful run. |

To see why a run failed, open **Columns** above the grid and tick
**Last Error**.

## How generation behaves

- The new file is written alongside the old one and swapped in only when it is
  complete. If a run fails, the previous file stays in place.
- The same feed cannot run twice at once. A second request while one is
  running is refused.
- Products are read in chunks so large catalogues do not exhaust memory. The
  chunk size is under **Stores > Configuration > UpturnStudio > Google Feeds >
  Feed Generation > Batch Size** (default 500). Lower it if runs hit the
  memory limit.
- The feed URL never changes for a feed, however often it is regenerated.

As a guide, a catalogue of about 2,000 products generated in 10 to 20 seconds
on a development machine.

## Timing with Merchant Center

Merchant Center fetches the file on its own schedule. Set its fetch time after
your generation time, leaving room for the run to finish. If you change prices
or stock often, generate more than once a day and use Merchant Center's
automatic item updates to cover the gaps.

## The queue consumer

Scheduled and **Generate Now** runs go through Magento's message queue. The
consumer is called `upturnstudio_googlefeed.feed_generate` and is started by
Magento's cron. If your server runs consumers separately, add it to that list,
or start it by hand:

```bash
bin/magento queue:consumers:start upturnstudio_googlefeed.feed_generate
```
