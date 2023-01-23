<p align="center"><img src="./src/icon.svg" width="100" height="100" alt="BigCommerce icon"></p>

<h1 align="center">BigCommerce for Craft CMS</h1>

Build a content-driven storefront by BigCommerce [BigCommerce](https://bigcommerce.com) products into [Craft CMS](https://craftcms.com/).

## Topics

- :package: [Installation](#installation): Set up the plugin and get connected to BigCommerce.
- :card_file_box: [Working with Products](#product-element): Learn what kind of data is available and how to access it.
- :bookmark_tabs: [Templating](#templating): Tips and tricks for using products in Twig.
- :telescope: [Advanced Features](#going-further): Go further with your integration.

## Installation

The BigCommerce plugin requires Craft CMS 4.0.0 or later.

To install the plugin, visit the [Plugin Store](https://plugins.craftcms.com/bigcommerce) from your Craft project, or follow these instructions.

1. Navigate to your Craft project in a new terminal:

   ```bash
   cd /path/to/project
   ```

2. Require the package with Composer:

   ```bash
   composer require craftcms/bigcommerce -w
   ```

3. In the Control Panel, go to **Settings** → **Plugins** and click the “Install” button for BigCommerce, or run:

   ```bash
   php craft plugin/install bigcommerce
   ```

### Create a BigCommerce App

The plugin works with BigCommerce’s [Custom Apps](https://help.bigcommerce.com/en/manual/apps/custom-apps) system.

> **Note**  
> If you are not the owner of the BigCommerce store, have the owner add you as a collaborator or staff member with the [_Develop Apps_ permission](https://help.bigcommerce.com/en/manual/apps/custom-apps#api-scope-permissions-for-custom-apps).

Follow [BigCommerce’s directions](https://help.bigcommerce.com/en/manual/apps/custom-apps) for creating a private app (through the _Get the API credentials for a custom app_ section), and take these actions when prompted:

1. **App Name**: Choose something that identifies the integration, like “Craft CMS.”
2. **Admin API access scopes**: The following scopes are required for the plugin to function correctly:

   - `read_products`
   - `read_product_listings`

   Additionally (at the bottom of this screen), the **Webhook subscriptions** &rarr; **Event version** should be `2022-10`.

3. **Admin API access token**: Reveal and copy this value into your `.env` file, as `SHOPIFY_ADMIN_ACCESS_TOKEN`.
4. **API key and secret key**: Reveal and/or copy the **API key** and **API secret key** into your `.env` under `SHOPIFY_API_KEY` and `SHOPIFY_API_SECRET_KEY`, respectively.

#### Store Hostname

The last piece of info you’ll need on hand is your store’s hostname. This is usually what appears in the browser when using the BigCommerce admin—it’s also shown in the Settings screen of your store:

<img src="./docs/bigcommerce-hostname.png" alt="Screenshot of the settings screen in the BigCommerce admin, with an arrow pointing to the store’s default hostname in the sidebar.">

Save this value (_without_ the leading `http://` or `https://`) in your `.env` as `SHOPIFY_HOSTNAME`. At this point, you should have four BigCommerce-specific values:

```env
# ...

SHOPIFY_ADMIN_ACCESS_TOKEN="..."
SHOPIFY_API_KEY="..."
SHOPIFY_API_SECRET_KEY="..."
SHOPIFY_HOSTNAME="my-storefront.mybigcommerce.com"
```

### Connect Plugin

Now that you have credentials for your custom app, it’s time to add them to Craft.

1. Visit the **BigCommerce** &rarr; **Settings** screen in your project’s control panel.
2. Assign the four environment variables to the corresponding settings, using the special [config syntax](https://craftcms.com/docs/4.x/config/#control-panel-settings):
   - **API Key**: `$SHOPIFY_API_KEY`
   - **API Secret Key**: `$SHOPIFY_API_SECRET_KEY`
   - **Access Token**: `$SHOPIFY_ACCESS_TOKEN`
   - **Host Name**: `$SHOPIFY_HOSTNAME`
3. Click **Save**.

> **Note**  
> These settings are stored in [Project Config](https://craftcms.com/docs/4.x/project-config.html), and will be automatically applied in other environments. [Webhooks](#set-up-webhooks) will still need to be configured for each environment!

### Set up Webhooks

Once your credentials have been added to Craft, a new **Webhooks** tab will appear in the **BigCommerce** section of the control panel.

Click **Create** on the Webhooks screen to add the required webhooks to BigCommerce. The plugin will use the credentials you just configured to perform this operation—so this also serves as an initial communication test.

> **Warning**  
> You will need to add webhooks for each environment you deploy the plugin to, because each webhook is tied to a specific URL.

> **Note**  
> If you need to test live synchronization in development, we recommend using [ngrok](https://ngrok.com/) to create a tunnel to your local environment. DDEV makes this simple, with [the `ddev share` command](https://ddev.readthedocs.io/en/latest/users/topics/sharing/). Keep in mind that your site’s primary/base URL is used when registering webhooks, so you may need to update it to match the ngrok tunnel, then recreate your webhooks.

## Product Element

Products from your BigCommerce store are represented in Craft as product [elements](https://craftcms.com/docs/4.x/elements.html), and can be found by going to **BigCommerce** &rarr; **Products** in the control panel.

### Synchronization

Products will be automatically created, updated, and deleted via [webhooks](#set-up-webhooks)—but Craft doesn’t know about a product until a change happens.

Once the plugin has been configured, perform an initial synchronization via the command line:

    php craft bigcommerce/sync/products

> **Note**  
> Products can also be synchronized from the control panel using the **BigCommerce Sync** utility. Keep in mind that large stores (over a hundred products) may take some time to synchronize, and can quickly run through [PHP’s `max_execution_time`](https://www.php.net/manual/en/info.configuration.php#ini.max-execution-time).

### Native Attributes

In addition to the standard element attributes like `id`, `title`, and `status`, each BigCommerce product element contains the following mappings to its canonical [BigCommerce Product resource](https://bigcommerce.dev/api/admin-rest/2022-10/resources/product#resource-object):

| Attribute        | Description                                                                                                                                                                                | Type       |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------- |
| `bigcommerceId`      | The unique product identifier in your BigCommerce store.                                                                                                                                       | `String`   |
| `bigcommerceStatus`  | The status of the product in your BigCommerce store. Values can be `active`, `draft`, or `archived`.                                                                                           | `String`   |
| `handle`         | The product’s “URL handle” in BigCommerce, equivalent to a “slug” in Craft. For existing products, this is visible under the **Search engine listing** section of the edit screen.             | `String`   |
| `productType`    | The product type of the product in your BigCommerce store.                                                                                                                                     | `String`   |
| `bodyHtml`       | Product description. Use the `\|raw` filter to output it in Twig—but only if the content is trusted.                                                                                       | `String`   |
| `publishedScope` | Published scope of the product in BigCommerce store. Common values are `web` (for web-only products) and `global` (for web and point-of-sale products).                                        | `String`   |
| `tags`           | Tags associated with the product in BigCommerce.                                                                                                                                               | `Array`    |
| `templateSuffix` | [Liquid template suffix](https://bigcommerce.dev/themes/architecture/templates#name-structure) used for the product page in BigCommerce.                                                           | `String`   |
| `vendor`         | Vendor of the product.                                                                                                                                                                     | `String`   |
| `metaFields`     | [Metafields](https://bigcommerce.dev/api/admin-rest/2022-10/resources/metafield#resource-object) associated with the product.                                                                  | `Array`    |
| `images`         | Images attached to the product in BigCommerce. The complete [Product Image resources](https://bigcommerce.dev/api/admin-rest/2022-10/resources/product-image#resource-object) are stored in Craft. | `Array`    |
| `options`        | Product options, as configured in BigCommerce. Each option has a `name`, `position`, and an array of `values`.                                                                                 | `Array`    |
| `createdAt`      | When the product was created in your BigCommerce store.                                                                                                                                        | `DateTime` |
| `publishedAt`    | When the product was published in your BigCommerce store.                                                                                                                                      | `DateTime` |
| `updatedAt`      | When the product was last updated in your BigCommerce store.                                                                                                                                   | `DateTime` |

All of these properties are available when working with a product element [in your templates](#templating).

> **Note**  
> See the BigCommerce documentation on the [product resource](https://bigcommerce.dev/api/admin-rest/2022-10/resources/product#resource-object) for more information about what kinds of values to expect from these properties.

### Methods

The product element has a few methods you might find useful in your [templates](#templating).

#### `Product::getVariants()`

Returns the [variants](#variants-and-pricing) belonging to the product.

```twig
{% set variants = product.getVariants() %}

<select name="variantId">
  {% for variant in variants %}
    <option value="{{ variant.id }}">{{ variant.title }}</option>
  {% endfor %}
</select>
```

#### `Product::getDefaultVariant()`

Shortcut for getting the first/default [variant](#variants-and-pricing) belonging to the product.

```twig
{% set products = craft.bigcommerceProducts.all() %}

<ul>
  {% for product in products %}
    {% set defaultVariant = product.getDefaultVariant() %}

    <li>
      <a href="{{ product.url }}">{{ product.title }}</a>
      <span>{{ defaultVariant.price|currency }}</span>
    </li>
  {% endfor %}
</ul>
```

#### `Product::getCheapestVariant()`

Shortcut for getting the lowest-priced [variant](#variants-and-pricing) belonging to the product.

```twig
{% set cheapestVariant = product.getCheapestVariant() %}

Starting at {{ cheapestVariant.price|currency }}!
```

#### `Product::getBigCommerceUrl()`

```twig
{# Get a link to the product’s page on BigCommerce: #}
<a href="{{ product.getBigCommerceUrl() }}">View on our store</a>

{# Link to a product with a specific variant pre-selected: #}
<a href="{{ product.getBigCommerceUrl({ variant: variant.id }) }}">Buy now</a>
```

#### `Product::getBigCommerceEditUrl()`

For your administrators, you can even link directly to the BigCommerce admin:

```twig
{# Assuming you’ve created a custom group for BigCommerce admin: #}
{% if currentUser and currentUser.isInGroup('clerks') %}
  <a href="{{ product.getBigCommerceEditUrl() }}">Edit product on BigCommerce</a>
{% endif %}
```

### Custom Fields

Products synchronized from BigCommerce have a dedicated field layout, which means they support Craft’s full array of [content tools](https://craftcms.com/docs/4.x/fields.html).

The product field layout can be edited by going to **BigCommerce** &rarr; **Settings** &rarr; **Products**, and scrolling down to **Field Layout**.

### Routing

You can give synchronized products their own on-site URLs. To set up the URI format (and the template that will be loaded when a product URL is requested), go to **BigCommerce** &rarr; **Settings** &rarr; **Products**.

If you would prefer your customers to view individual products on BigCommerce, clear out the **Product URI Format** field on the settings page, and use `product.bigcommerceUrl` instead of `product.url` in your templates.

### Product Status

A product’s `status` in Craft is a combination of its `bigcommerceStatus` attribute ('active', 'draft', or 'archived') and its enabled state. The former can only be changed from BigCommerce; the latter is set in the Craft control panel.

> **Note**  
> Statuses in Craft are often a synthesis of multiple properties. For example, an entry with the _Pending_ status just means it is `enabled` _and_ has a `postDate` in the future.

In most cases, you’ll only need to display “Live” products, or those which are _Active_ in BigCommerce and _Enabled_ in Craft:

| Status            | BigCommerce  | Craft    |
| ----------------- | -------- | -------- |
| `live`            | Active   | Enabled  |
| `bigcommerceDraft`    | Draft    | Enabled  |
| `bigcommerceArchived` | Archived | Enabled  |
| `disabled`        | Any      | Disabled |

## Querying Products

Products can be queried like any other element in the system.

A new query begins with the `craft.bigcommerceProducts` factory function:

```twig
{% set products = craft.bigcommerceProducts.all() %}
```

### Query Parameters

The following element query parameters are supported, in addition to [Craft’s standard set](https://craftcms.com/docs/4.x/element-queries.html).

> **Note**
> Fields stored as JSON (like `options` and `metadata`) are only queryable as plain text. If you need to do advanced organization or filtering, we recommend using custom Category or Tag fields in your Product [field layout](#custom-fields).

#### `bigcommerceId`

Filter by BigCommerce product IDs.

```twig
{# Watch out—these aren't the same as element IDs! #}
{% set singleProduct = craft.bigcommerceProducts
  .bigcommerceId(123456789)
  .one() %}
```

#### `bigcommerceStatus`

Directly query against the product’s status in BigCommerce.

```twig
{% set archivedProducts = craft.bigcommerceProducts
  .bigcommerceStatus('archived')
  .all() %}
```

Use the regular `.status()` param if you'd prefer to query against [synthesized status values](#product-status).

#### `handle`

Query by the product’s handle, in BigCommerce.

```twig
{% set product = craft.bigcommerceProducts
  .handle('worlds-tallest-socks')
  .all() %}
```

> :rotating_light: This is not a reliable means to fetch a specific product, as the value may change during a synchronization. If you want a permanent reference to a product, consider using the BigCommerce [product field](#product-field).

#### `productType`

Find products by their “type” in BigCommerce.

```twig
{% set upSells = craft.bigcommerceProducts
  .productType(['apparel', 'accessories'])
  .all() %}
```

#### `publishedScope`

Show only products that are published to a matching sales channel.

```twig
{# Only web-ready products: #}
{% set webProducts = craft.bigcommerceProducts
  .publishedScope('web')
  .all() %}

{# Everything: #}
{% set inStoreProducts = craft.bigcommerceProducts
  .publishedScope('global')
  .all() %}
```

#### `tags`

Tags are stored as a comma-separated list. You may see better results using [the `.search()` param](https://craftcms.com/docs/4.x/searching.html#development).

```twig
{# Find products whose tags include the term in any position, with variations on casing: #}
{% set clogs = craft.bigcommerceProducts
  .tags(['*clog*', '*Clog*'])
  .all() %}
```

#### `vendor`

Filter by the vendor information from BigCommerce.

```twig
{# Find products with a vendor matching either option: #}
{% set fancyBags = craft.bigcommerceProducts
  .vendor(['Louis Vuitton', 'Jansport'])
  .all() %}
```

#### `images`

Images are stored as a blob of JSON, and only intended for use in a template in conjunction with a loaded product. Filtering directly by [image resource](https://bigcommerce.dev/api/admin-rest/2022-10/resources/product-image#resource-object) values can be difficult and unpredictable—you may see better results using [the `.search()` param](https://craftcms.com/docs/4.x/searching.html#development).

```twig
{# Find products that have an image resource mentioning "stripes": #}
{% set clogs = craft.bigcommerceProducts
  .images('*stripes*')
  .all() %}
```

#### `options`

[Options](#using-options) are stored as a blob of JSON, and only intended for use in a template in conjunction with a loaded product. You may see better results using [the `.search()` param](https://craftcms.com/docs/4.x/searching.html#development).

```twig
{# Find products that use a "color" option: #}
{% set clogs = craft.bigcommerceProducts
  .options('"Color"')
  .all() %}
```

The above includes quote (`"`) literals, because it’s attempting to locate a specific key in a JSON array, which will always be surrounded by double-quotes.

## Templating

### Product Data

Products behave just like any other element, in Twig. Once you’ve loaded a product via a [query](#querying-products) (or have a reference to one on its template), you can output its native [BigCommerce attributes](#native-attributes) and [custom field](#custom-fields) data.

> **Note**  
> Some attributes are stored as JSON, which limits nested properties’s types. As a result, dates may be slightly more difficult to work with.

```twig
{# Standard element title: #}
{{ product.title }}
  {# -> Root Beer #}

{# BigCommerce HTML content: #}
{{ product.bodyHtml|raw }}
  {# -> <p>...</p> #}

{# Tags, as list: #}
{{ product.tags|join(', ') }}
  {# -> sweet, spicy, herbal #}

{# Tags, as filter links: #}
{% for tag in tags %}
  <a href="{{ siteUrl('products', { tag: tag }) }}">{{ tag|title }}</a>
  {# -> <a href="https://mydomain.com/products?tag=herbal">Herbal</a> #}
{% endfor %}

{# Images: #}
{% for image in product.images %}
  <img src="{{ image.src }}" alt="{{ image.alt }}">
    {# -> <img src="https://cdn.bigcommerce.com/..." alt="Bubbly Soda"> #}
{% endfor %}

{# Variants: #}
<select name="variantId">
  {% for variant in product.getVariants() %}
    <option value="{{ variant.id }}">{{ variant.title }} ({{ variant.price|currency }})</option>
  {% endfor %}
</select>
```

### Variants and Pricing

Products don’t have a price, despite what the BigCommerce UI might imply—instead, every product has at least one
[Variant](https://bigcommerce.dev/api/admin-rest/2022-10/resources/product-variant#resource-object).

You can get an array of variant objects for a product by calling [`product.getVariants()`](#productgetvariants). The product element also provides convenience methods for getting the [default](#productgetdefaultvariant) and [cheapest](#productgetcheapestvariant) variants, but you can filter them however you like with Craft’s [`collect()`](https://craftcms.com/docs/4.x/dev/functions.html#collect) Twig function.

Unlike products, variants in Craft…

- …are represented exactly as [the API](https://bigcommerce.dev/api/admin-rest/2022-10/resources/product-variant#resource-object) returns them;
- …use BigCommerce’s convention of underscores in property names instead of exposing [camel-cased equivalents](#native-attributes);
- …are plain associative arrays;
- …have no methods of their own;

Once you have a reference to a variant, you can output its properties:

```twig
{% set defaultVariant = product.getDefaultVariant() %}

{{ defaultVariant.price|currency }}
```

> **Note**  
> The built-in [`currency`](https://craftcms.com/docs/4.x/dev/filters.html#currency) Twig filter is a great way to format money values.

### Using Options

Options are BigCommerce’s way of distinguishing variants on multiple axes.

If you want to let customers pick from options instead of directly select variants, you will need to resolve which variant a given combination points to.

<details>
<summary>Form</summary>

```twig
<form id="add-to-cart" method="post" action="{{ craft.bigcommerce.store.getUrl('cart/add') }}">
  {# Create a hidden input to send the resolved variant ID to BigCommerce: #}
  {{ hiddenInput('id', null, {
    id: 'variant',
    data: {
      variants: product.variants,
    },
  }) }}

  {# Create a dropdown for each set of options: #}
  {% for option in product.options %}
    <label>
      {{ option.name }}
      {# The dropdown includes the option’s `position`, which helps match it with the variant, later: #}
      <select data-option="{{ option.position }}">
        {% for val in option.values %}
          <option value="{{ val }}">{{ val }}</option>
        {% endfor %}
      </select>
    </label>
  {% endfor %}

  <button>Add to Cart</button>
</form>
```

</details>

<details>

<summary>Script</summary>

The code below can be added to a [`{% js %}` tag](https://craftcms.com/docs/4.x/dev/tags.html#js), alongside the form code.

```js
// Store references to <form> elements:
const $form = document.getElementById("add-to-cart");
const $variantInput = document.getElementById("variant");
const $optionInputs = document.querySelectorAll("[data-option]");

// Create a helper function to test a map of options against known variants:
const findVariant = (options) => {
  const variants = JSON.parse($variantInput.dataset.variants);

  // Use labels for the inner and outer loop so we can break out early:
  variant: for (const v in variants) {
    option: for (const o in options) {
      // Option values are stored as `option1`, `option2`, or `option3` on each variant:
      if (variants[v][`option${o}`] !== options[o]) {
        // Didn't match one of the options? Bail:
        continue variant;
      }
    }

    // Nice, all options matched this variant! Return it:
    return variants[v];
  }
};

// Listen for change events on the form, rather than the individual option menus:
$form.addEventListener("change", (e) => {
  const selectedOptions = {};

  // Loop over option menus and build an object of selected values:
  $optionInputs.forEach(($input) => {
    // Add the value under the "position" key
    selectedOptions[$input.dataset.option] = $input.value;
  });

  // Use our helper function to resolve a variant:
  const variant = findVariant(selectedOptions);

  if (!variant) {
    console.warn("No variant exists for options:", selectedOptions);

    return;
  }

  // Assign the resolved variant’s ID to the hidden input:
  $variantInput.value = variant.id;
});

// Trigger an initial `change` event to simulate a selection:
$form.dispatchEvent(new Event("change"));
```

</details>

### Cart

Your customers can add products to their cart directly from your Craft site:

```twig
{% set product = craft.bigcommerceProducts.one() %}

<form action="{{ craft.bigcommerce.store.getUrl('cart/add') }}" method="post">
  <select name="id">
    {% for variant in product.getVariants() %}
      <option value="{{ variant.id }}">{{ variant.title }}</option>
    {% endfor %}
  </select>

  {{ hiddenInput('qty', 1) }}

  <button>Add to Cart</button>
</form>
```

### JS Buy SDK

Cart management and checkout are not currently supported in a native way.

However, BigCommerce maintains the [Javascript Buy SDK](https://bigcommerce.dev/custom-storefronts/tools/js-buy) as a means of interacting with their [Storefront API](https://bigcommerce.dev/api/storefront) to create completely custom shopping experiences.

> **Note**  
> Use of the Storefront API requires a different [access key](https://help.bigcommerce.com/en/manual/apps/custom-apps#update-storefront-api-access-scopes-for-a-custom-app), and assumes that you have published your products into the Storefront app’s [sales channel](https://bigcommerce.dev/custom-storefronts/tools/js-buy#step-2-make-your-products-and-collections-available).
>
> Your public Storefront API token can be stored with your other credentials in `.env` and output in your front-end with the `{{ getenv('...') }}` Twig helper—or just baked into a Javascript bundle. **Keep your other secrets safe!** This is the only one that can be disclosed.

The plugin makes no assumptions about how you use your product data in the front-end, but provides the tools necessary to connect it with the SDK. As an example, let’s look at how you might render a list of products in Twig, and hook up a custom client-side cart…

#### Shop Template: `templates/shop.twig`

```twig
{# Include the Buy SDK on this page: #}
{% do view.registerJsFile('https://sdks.bigcommercecdn.com/js-buy-sdk/v2/latest/index.umd.min.js') %}

{# Register your own script file (see “Custom Script,” below): #}
{% do view.registerJsFile('/assets/js/shop.js') %}

{# Load some products: #}
{% set products = craft.bigcommerceProducts().all() %}

<ul>
  {% for product in products %}
    {# For now, we’re only handling a single variant: #}
    {% set defaultVariant = product.getVariants()|first %}

    <li>
      {{ product.title }}
      <button
        class="buy-button"
        data-default-variant-id="{{ defaultVariant.id }}">Add to Cart</button>
    </li>
  {% endfor %}
</ul>
```

#### Custom Script: `assets/js/shop.js`

```js
// Initialize a client:
const client = BigCommerceBuy.buildClient({
  domain: "my-storefront.mybigcommerce.com",
  storefrontAccessToken: "...",
});

// Create a simple logger for the cart’s state:
const logCart = (c) => {
  console.log(c.lineItems);
  console.log(`Checkout URL: ${c.webUrl}`);
};

// Create a cart or “checkout” (or perhaps load one from `localStorage`):
client.checkout.create().then((checkout) => {
  const $buyButtons = document.querySelectorAll(".buy-button");

  // Add a listener to each button:
  $buyButtons.forEach(($b) => {
    $b.addEventListener("click", (e) => {
      // Read the variant ID off the product:
      client.checkout
        .addLineItems(checkout.id, [
          {
            // Build the Storefront-style resource identifier:
            variantId: `gid://bigcommerce/ProductVariant/${$b.dataset.defaultVariantId}`,
            quantity: 1,
          },
        ])
        .then(logCart); // <- Log the changes!
    });
  });
});
```

### Buy Button JS

The above example can be simplified with the [Buy Button JS](https://bigcommerce.dev/custom-storefronts/tools/buy-button), which provides some ready-made UI components, like a fully-featured cart. The principles are the same:

1. Make products available via the appropriate sales channels in BigCommerce;
2. Output synchronized product data in your front-end;
3. Initialize, attach, or trigger SDK functionality in response to events, using BigCommerce-specific identifiers from step #2;

### Checkout

While solutions exist for creating a customized shopping experience, _checkout will always happen on BigCommerce’s platform_. This is not a technical limitation so much as it is a policy—BigCommerce’s checkout flow is fast, reliable, secure, and familiar to many shoppers.

If you want your customers’ entire journey to be kept on-site, we encourage you to try out our powerful ecommerce plugin, [Commerce](https://craftcms.com/commerce).

### Helpers

In addition to [product element methods](#methods), the plugin exposes its API to Twig via `craft.bigcommerce`.

#### API Service

> **Warning**  
> Use of API calls in Twig blocks rendering and—depending on traffic—may cause timeouts and/or failures due to rate limits. Consider using the [`{% cache %}` tag](https://craftcms.com/docs/4.x/dev/tags.html#cache) with a key and specific expiry time to avoid making a request every time a template is rendered:
>
> ```twig
> {% cache using key "bigcommerce:collections" for 10 minutes %}
>   {# API calls + output... #}
> {% endcache %}
> ```

Issue requests to the BigCommerce Admin API via `craft.bigcommerce.api`:

```twig
{% set req = craft.bigcommerce.api.get('custom_collections') %}
{% set collections = req.response.custom_collections %}
```

The schema for each API resource will differ. Consult the [BigCommerce API documentation](https://bigcommerce.dev/api/admin-rest) for more information.

#### Store Service

A simple URL generator is available via `craft.bigcommerce.store`. You may have noticed it in the [cart](#cart) example, above—but it is a little more versatile than that!

```twig
{# Create a link to add a product/variant to the cart: #}
{{ tag('a', {
  href: craft.bigcommerce.store.getUrl('cart/add', {
    id: variant.id
  }),
  text: 'Add to Cart',
  target: '_blank',
}) }}
```

The same params argument can be passed to a product element’s `getBigCommerceUrl()` method:

```twig
{% for variant in product.getVariants() %}
  <a href="{{ product.getBigCommerceUrl({ id: variant.id }) }}">{{ variant.title }}</a>
{% endfor %}
```

## Product Field

The plugin provides a _BigCommerce Products_ field, which uses the familiar [relational field](https://craftcms.com/docs/4.x/relations.html) UI to allow authors to select Product elements.

Relationships defined with the _BigCommerce Products_ field use stable element IDs under the hood. When BigCommerce products are archived or deleted, the corresponding elements will also be updated in Craft, and naturally filtered out of your query results—including those explicitly attached via a _BigCommerce Products_ field.

> **Note**  
> Upgrading? Check out the [migration](#migrating-from-v2x) notes for more info.

---

## Migrating from v2.x

If you are upgrading a Craft 3 project to Craft 4 and have existing “BigCommerce Product” fields, you’ll need show the plugin how to translate plain BigCommerce IDs (stored as a JSON array) into element IDs, within Craft’s relations system.

> **Warning**  
> Before getting started with the field data migration, make sure you have [synchronized](#synchronization) your product catalog.

It’s safe to remove the old plugin package (`nmaier95/bigcommerce-product-fetcher`) from your `composer.json`—but **do not use the control panel to uninstall it**. We want the field’s _data_ to stick around, but don’t need the old field _class_ to work with it.

> **Note**  
> You may see a “missing field” in your field layouts during this process—that’s OK! Your data is still there.

For each legacy BigCommerce Product field in your project, do the following:

1. Create a _new_ [BigCommerce Products](#product-field) field, giving it a a new handle and name;
2. Add the field to any layouts where the legacy field appeared;

### Re-saving Data

Run the following command (substituting appropriate values) for each place you added the field in step #2, above:

- `resave/entries` &rarr; The [re-save command](https://craftcms.com/docs/4.x/console-commands.html#resave) for the element type the field layout is attached to;
- `mySectionHandle` &rarr; A stand-in for any criteria that need to be applied to the element type you’re re-saving;
- `oldBigCommerceField` &rarr; Field handle from the old version of the plugin (used inside the `--to` argument closure);
- `newBigCommerceField` &rarr; New field handle created in step #1, above;

  ```bash
  php craft resave/entries \
    --section=mySectionHandle \
    --set=newBigCommerceField \
    --to="fn(\$entry) => collect(json_decode(\$entry->oldBigCommerceField))->map(fn (\$id) => \venveo\bigcommerce\Plugin::getInstance()->getProducts()->getProductIdByBigCommerceId(\$id))->unique()->all()"
  ```

### Updating Templates

After your content is re-saved, update your templates:

#### Before

In v2.x, you were responsible for looking up product details in the template:

```twig
{# Product references were stored as a list of IDs: #}
{% set productIds = entry.oldBigCommerceField %}

<ul>
  {% for productId in productIds %}
    {# Query BigCommerce API for Product using ID: #}
    {% set bigcommerceProduct = craft.bigcommerce.getProductById({ id: productId }) %}

    <li>{{ product.productType }}: {{ product.title }}</li>
  {% endfor %}
</ul>
```

#### After

There is no need to query the BigCommerce API to render product details in your templates—all of the data is available on the returned elements!

```twig
{# Execute element query from your new relational field: #}
{% set relatedProducts = entry.newBigCommerceField.all() %}

<ul>
  {% for product in products %}
    {# Output product data directly: #}
    <li>{{ product.productType }}: {{ product.title }}</li>
  {% endfor %}
</ul>
```

## Going Further

### Events

#### `venveo\bigcommerce\services\Products::EVENT_BEFORE_SYNCHRONIZE_PRODUCT`

Emitted just prior to a product element being saved with new BigCommerce data. The `venveo\bigcommerce\events\BigCommerceProductSyncEvent` extends `craft\events\CancelableEvent`, so setting `$event->isValid` allows you to prevent the new data from being saved.

The event object has three properties:

- `element`: The product element being updated.
- `source`: The BigCommerce product object that was applied.
- `metafields`: Additional metafields from BigCommerce that the plugin discovered while performing the synchronization.

```php
use venveo\bigcommerce\events\BigCommerceProductSyncEvent;
use venveo\bigcommerce\services\Products;
use yii\base\Event;

Event::on(
  Products::class,
  Products::EVENT_BEFORE_SYNCHRONIZE_PRODUCT,
  function(BigCommerceProductSyncEvent $event) {
    // Example 1: Cancel the sync if a flag is set via a BigCommerce metafield:
    if ($event->metafields['do_not_sync'] ?? false) {
      $event->isValid = false;
    }

    // Example 2: Set a field value from metafield data:
    $event->element->setFieldValue('myNumberFieldHandle', $event->metafields['cool_factor']);
  }
);
```

> **Warning**
> Do not manually save changes made in this event handler. The plugin will take care of this for you!

### Element API

Your synchronized products can be published into an [Element API](https://plugins.craftcms.com/element-api) endpoint, just like any other element type. This allows you to set up a local JSON feed of products, decorated with any content you’ve added in Craft:

```php
use venveo\bigcommerce\elements\Product;

return [
  'endpoints' => [
    'products.json' => function() {
      return [
        'elementType' => Product::class,
        'criteria' => [
          'publishedScope' => 'web',
          'with' => [
            ['myImageField']
          ],
        ],
        'transformer' => function(Product $product) {
          $image = $product->myImageField->one();

          return [
            'title' => $product->title,
            'variants' => $product->getVariants(),
            'image' => $image ? $image->getUrl() : null,
          ];
        },
      ];
    },
  ],
];
```


### Acknowledgements

- Very helpful reference repository: https://github.com/labbydev/fingerprint