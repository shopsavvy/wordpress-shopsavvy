=== ShopSavvy Price Comparison ===
Contributors: shopsavvy
Tags: price comparison, shopping, deals, barcode, woocommerce
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display real-time price comparisons from thousands of retailers using Gutenberg blocks, shortcodes, and widgets.

== Description ==

ShopSavvy Price Comparison lets you embed live price comparisons on any WordPress site — blogs, content sites, review sites, and affiliate sites. Show your readers the best available prices across thousands of retailers in real time.

**Features:**

* **Gutenberg Block** — Drop the "ShopSavvy Price Comparison" block into any post or page with full editor preview and sidebar controls.
* **Shortcode** — Use `[shopsavvy_price identifier="B08N5WRWNW"]` anywhere shortcodes are supported.
* **Widget** — Add a compact price comparison to any sidebar or widget area.
* **REST API Endpoint** — Optional `wp-json/shopsavvy/v1/search` endpoint for front-end JavaScript or external integrations, with rate limiting.
* **Three Display Styles** — Table (full comparison), Card (visual grid), and Inline ("From $149.99 at Amazon").
* **Smart Caching** — Transient-based caching reduces API calls and speeds up page loads. Configurable from 5 minutes to 24 hours.
* **API Usage Meter** — See your API usage at a glance in the settings page.

**Supported Identifiers:**

* UPC / EAN barcodes
* Amazon ASINs
* Product URLs
* Model numbers
* Manufacturer Part Numbers (MPN)

**Requirements:**

* A ShopSavvy Data API key — [Get one at shopsavvy.com/data](https://shopsavvy.com/data)
* WordPress 5.0+ with PHP 8.0+

== Installation ==

1. Upload the `wordpress-shopsavvy` folder to `/wp-content/plugins/`.
2. Activate "ShopSavvy Price Comparison" in Plugins.
3. Go to **Settings > ShopSavvy** and enter your API key.
4. Start using the Gutenberg block, shortcode, or widget.

== Frequently Asked Questions ==

= Where do I get an API key? =

Visit [shopsavvy.com/data](https://shopsavvy.com/data) to sign up for the ShopSavvy Data API.

= What product identifiers are supported? =

You can use UPC/EAN barcodes, Amazon ASINs, product URLs, model numbers, or manufacturer part numbers (MPN).

= How does caching work? =

API responses are cached using WordPress transients. You can configure the cache duration in Settings > ShopSavvy, from 5 minutes to 24 hours. Longer durations make fewer API calls.

= Can I use this without WooCommerce? =

Yes. This plugin works on any WordPress site — it does not require WooCommerce.

= Is the REST endpoint secure? =

The REST endpoint is disabled by default. When enabled, it uses IP-based rate limiting (10 requests per minute) and supports WordPress nonce verification for logged-in users.

== Screenshots ==

1. Table display style showing retailer, price, condition, and action link.
2. Card display style with "Best Price" badge.
3. Settings page with API key, cache, and style configuration.
4. Gutenberg block editor with live preview.

== Changelog ==

= 1.0.0 =
* Initial release.
* Gutenberg block with editor preview.
* [shopsavvy_price] shortcode with table, card, and inline styles.
* Sidebar widget.
* REST API endpoint with rate limiting.
* Settings page with API key validation and API usage meter.
* Transient-based caching with configurable duration.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
