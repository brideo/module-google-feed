# Subscription and API access

Everything in this module works without a subscription: feeds, ad click
capture and the API are all included.

A subscription adds the reporting service on top. It reads your feed and order
data and gives you:

- suggestions for improving product titles, descriptions and other feed
  content,
- Google Ads spend against real sales and return per product.

To use it you need a subscription key, and a way for the service to reach your
store. The service connects through the [AI connector](ai-connector.md), which
is how it reads and manages your feeds. It can also use the REST routes
described below for attribute values and orders.

## Subscription key

**Stores > Configuration > UpturnStudio > Google Feeds > Subscription**

![Configuration](images/configuration.jpg)

1. Paste your key into **Subscription Key**.
2. Click **Save Config**.

The key is stored encrypted and checked when you save and once a day after
that.

| | Without a key | With a key |
|---|---|---|
| Product feeds | Yes | Yes |
| Ad click capture | Yes | Yes |
| AI connector tools and REST routes | Yes | Yes |
| Content improvement suggestions | No | Yes, in the reporting service |
| Ad spend vs ROI reporting | No | Yes, in the reporting service |

If a key is not recognised, a notice appears in the admin. Nothing in your
store is switched off: feeds keep generating and your ads keep running. If the
subscription service cannot be reached, nothing changes.

> **Current release:** the subscription service is not connected yet, so a key
> is stored but not verified.

## Giving the reporting system access

There are two ways in, and either or both can be used.

### The AI connector (feeds, mapping, previews, settings)

Follow the [AI connector](ai-connector.md) guide. Everything that manages feeds
goes this way. The service signs in as an admin and acts within that admin's
role, so create a dedicated role for it. Tools that change data stay off until
you switch them on.

### REST (Google attribute values and orders)

For systems that prefer REST, create an integration that can use only the
routes you want.

1. Go to **System > Extensions > Integrations** and click **Add New
   Integration**.
2. Give it a name, such as *Google Feeds reporting*, and enter your admin
   password where asked.
3. Open the **API** tab and set **Resource Access** to **Custom**.
4. Tick only the resources under **Google Feeds > API and AI Connector** that
   it needs:
   - **Read and Write Google Attribute Values**
   - **Read Click-Attributed Orders**
5. Save, then click **Activate** in the grid and **Allow**.
6. Copy the **Access Token** and give it to the reporting system over a secure
   channel.

One more setting is needed for the token to work on its own. Under **Stores >
Configuration > Services > OAuth > Consumer Settings**, set **Allow OAuth
Access Tokens to be used as standalone Bearer tokens** to **Yes**.

Treat the access token like a password. To cut off access, deactivate or
delete the integration.

## What the reporting system can do

| It can | It cannot |
|---|---|
| List your feeds and their URLs | Create, change or delete feeds, unless you switch on edit tools for the AI connector |
| Read orders that came from an ad click, with their items | Read orders that did not come from an ad click |
| Set and remove Google attribute values per SKU | Change products, prices, stock or customers |

Product catalogue details beyond this need Magento's own API permissions,
which you grant separately if you choose to.

## Admin user permissions

To let a staff member manage feeds, edit their role under **System >
Permissions > User Roles** and tick **Google Feeds > Manage Feeds**. To let
them change the settings, also tick **Stores > Settings > Configuration >
Google Feeds Section**.

Developers: see the [AI connector tools reference](../docs/mcp-tools.md) and the [REST API reference](../docs/api.md).
