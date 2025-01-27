=== Fourthwall Products ===
Contributors: muddylemon
Tags: fourthwall, ecommerce, products, store, shop, merch
Requires at least: 5.6
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display your Fourthwall products on your WordPress site with an easy-to-use widget and shortcodes.

== Description ==

Fourthwall Products is a WordPress plugin that allows you to seamlessly integrate your Fourthwall store products into your WordPress website. Display your products in a responsive grid or list layout using either widgets or shortcodes.

= Features =

* Display products from your Fourthwall collections
* Responsive grid and list layouts
* Customizable display options
* Product image support
* Price display with sale prices
* Color and size variant display
* Stock level indicators
* Cache system for improved performance
* Multiple currency support
* Theme-overridable templates

= Widget Features =

* Title configuration
* Collection selection
* Currency selection
* Grid/List layout options
* Customizable number of columns
* Custom CSS class support

= Shortcode Support =

Display collections using the [fourthwall_products] shortcode:

`[fourthwall_products collection="your-collection-slug" layout="grid" columns="3" limit="12"]`

Display single products using the [fourthwall_product] shortcode:

`[fourthwall_product slug="your-product-slug" currency="USD"]`

= Available Currencies =

* USD - US Dollar
* EUR - Euro
* CAD - Canadian Dollar
* GBP - British Pound
* AUD - Australian Dollar
* NZD - New Zealand Dollar
* SEK - Swedish Krona
* NOK - Norwegian Krone
* DKK - Danish Krone
* PLN - Polish Złoty
* INR - Indian Rupee
* JPY - Japanese Yen
* MYR - Malaysian Ringgit
* SGD - Singapore Dollar

= Developer Friendly =

* Template override system
* Clear documentation
* Well-organized code
* Filter and action hooks
* Extendable architecture

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/fourthwall-products` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings > Fourthwall Products to configure your Storefront Token and other settings.
4. Add the widget to your sidebar or use the shortcodes in your posts/pages.

= Minimum Requirements =

* WordPress 5.6 or higher
* PHP 7.2 or higher
* A Fourthwall store account with API access

== Frequently Asked Questions ==

= Where do I find my Storefront Token? =

Your Storefront Token can be found in your Fourthwall dashboard under the API settings section. It typically starts with 'ptkn_'.

= Can I customize the product display? =

Yes! The plugin comes with a template override system. Copy the template files from the plugin's 'templates' directory to your theme's 'fourthwall' directory and customize them as needed.

= How often is the product data updated? =

By default, the plugin caches API responses for 1 hour. You can adjust this duration in the plugin settings.

= Can I display multiple collections? =

Yes, you can add multiple widgets or shortcodes, each displaying different collections.

= How do I change the currency display? =

You can set a default currency in the plugin settings, or specify a currency for each widget or shortcode instance.

== Screenshots ==

1. Product grid display
2. Product list display
3. Widget configuration
4. Plugin settings page

== Changelog ==

= 1.0.0 =
* Initial release
* Product display widget
* Shortcode support
* Basic settings page
* Cache system
* Template override support

== Upgrade Notice ==

= 1.0.0 =
Initial release

== Developer Documentation ==

= Template Override =

To override the product display template in your theme:

1. Create a 'fourthwall' directory in your theme
2. Copy `widget-template.php` from the plugin's 'templates' directory to your theme's 'fourthwall' directory
3. Customize the template as needed

= Available Filters =

* `fourthwall_cache_duration` - Modify cache duration
* `fourthwall_product_classes` - Modify product wrapper classes
* `fourthwall_price_format` - Customize price format

= Available Actions =

* `fourthwall_before_products` - Before products display
* `fourthwall_after_products` - After products display
* `fourthwall_before_product` - Before each product
* `fourthwall_after_product` - After each product

= Example: Customize Cache Duration =

`
add_filter('fourthwall_cache_duration', function($duration) {
    return 7200; // Set cache to 2 hours
});
`

== Credits ==

* Built with the Fourthwall API
* Uses WordPress coding standards
* GPL v2 licensed