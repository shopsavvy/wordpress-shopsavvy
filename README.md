# ShopSavvy Price Comparison for WordPress

Display real-time price comparisons from thousands of retailers on any WordPress site using Gutenberg blocks, shortcodes, and widgets.

## Features

- **Gutenberg Block** with live editor preview and sidebar controls
- **Shortcode** `[shopsavvy_price identifier="B08N5WRWNW" max="5" style="table"]`
- **Sidebar Widget** for compact price comparisons in any widget area
- **REST API Endpoint** at `wp-json/shopsavvy/v1/search` with rate limiting
- **Three Display Styles**: Table, Card, and Inline
- **Smart Caching** via WordPress transients (5 min to 24 hours)
- **API Usage Meter** in the admin settings

## Requirements

- WordPress 5.0+
- PHP 8.0+
- [ShopSavvy Data API key](https://shopsavvy.com/data)

## Installation

1. Download or clone this repository into `/wp-content/plugins/wordpress-shopsavvy/`
2. Activate the plugin in WordPress admin
3. Go to **Settings > ShopSavvy** and enter your API key

## Usage

### Gutenberg Block

Search for "ShopSavvy" in the block inserter. Configure the product identifier, max results, and display style in the block sidebar.

### Shortcode

```
[shopsavvy_price identifier="B08N5WRWNW"]
[shopsavvy_price identifier="B08N5WRWNW" max="5" style="table"]
[shopsavvy_price identifier="B08N5WRWNW" style="card"]
[shopsavvy_price identifier="B08N5WRWNW" style="inline"]
```

**Attributes:**

| Attribute    | Default | Description                                      |
|-------------|---------|--------------------------------------------------|
| `identifier` | —       | UPC, EAN, ASIN, URL, model number, or MPN        |
| `max`        | `5`     | Maximum number of offers to display               |
| `style`      | `table` | Display style: `table`, `card`, or `inline`       |

### Widget

Go to **Appearance > Widgets** and add "ShopSavvy Price Comparison" to any widget area. Configure the product identifier and max results.

### REST API

When enabled in settings, query products via:

```
GET /wp-json/shopsavvy/v1/search?id=B08N5WRWNW&type=offers&max=5
GET /wp-json/shopsavvy/v1/search?q=airpods&type=search&max=10
GET /wp-json/shopsavvy/v1/search?id=B08N5WRWNW&type=details
GET /wp-json/shopsavvy/v1/search?id=B08N5WRWNW&type=history
```

**Parameters:**

| Param  | Description                                                    |
|--------|----------------------------------------------------------------|
| `q`    | Search query (required when `type=search`)                     |
| `id`   | Product identifier (required for offers/details/history)       |
| `type` | Request type: `offers`, `details`, `search`, `history`         |
| `max`  | Maximum results (1-50, default 5)                              |

Rate limited to 10 requests per minute per IP.

## Supported Identifiers

- UPC / EAN barcodes
- Amazon ASINs
- Product URLs
- Model numbers
- Manufacturer Part Numbers (MPN)

## License

GPL v2 or later. See [LICENSE](LICENSE).
