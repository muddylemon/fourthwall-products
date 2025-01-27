<?php
/**
 * Plugin Name: Fourthwall Products
 * Plugin URI: https://yourwebsite.com/fourthwall-products
 * Description: Display Fourthwall products in your WordPress site using widgets and shortcodes.
 * Version: 1.0.0
 * Requires at least: 5.6
 * Requires PHP: 7.2
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: fourthwall-products
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package FourthwallProducts
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('FOURTHWALL_PRODUCTS_VERSION', '1.0.0');
define('FOURTHWALL_PRODUCTS_FILE', __FILE__);
define('FOURTHWALL_PRODUCTS_PATH', plugin_dir_path(__FILE__));
define('FOURTHWALL_PRODUCTS_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class Fourthwall_Products {
    /**
     * The single instance of the class
     *
     * @var Fourthwall_Products|null
     */
    protected static $instance = null;

    /**
     * Main Fourthwall_Products Instance
     * 
     * Ensures only one instance of Fourthwall_Products is loaded or can be loaded.
     *
     * @return Fourthwall_Products
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->include_files();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
        add_action('init', array($this, 'init'), 0);
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    /**
     * Include required files
     */
    private function include_files() {
        require_once FOURTHWALL_PRODUCTS_PATH . 'includes/class-api-client.php';
        require_once FOURTHWALL_PRODUCTS_PATH . 'includes/class-product-widget.php';
        require_once FOURTHWALL_PRODUCTS_PATH . 'includes/class-settings.php';
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Register shortcodes
        $this->register_shortcodes();
    }

    /**
     * Register shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('fourthwall_products', array($this, 'products_shortcode'));
        add_shortcode('fourthwall_product', array($this, 'product_shortcode'));
    }

    /**
     * Products shortcode callback
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function products_shortcode($atts) {
        $atts = shortcode_atts(array(
            'collection' => '',
            'currency' => '',
            'layout' => 'grid',
            'columns' => 3,
            'limit' => 12
        ), $atts, 'fourthwall_products');

        ob_start();

        if (empty($atts['collection'])) {
            return '<p class="fourthwall-error">' . 
                   esc_html__('Please specify a collection slug.', 'fourthwall-products') . 
                   '</p>';
        }

        $api_client = new Fourthwall_API_Client();
        $products = $api_client->get_collection_products(
            $atts['collection'],
            $atts['currency'],
            1,
            $atts['limit']
        );

        if (is_wp_error($products)) {
            return '<p class="fourthwall-error">' . esc_html($products->get_error_message()) . '</p>';
        }

        // Load template
        $template = locate_template('fourthwall/widget-template.php');
        if (!$template) {
            $template = FOURTHWALL_PRODUCTS_PATH . 'templates/widget-template.php';
        }

        $layout = $atts['layout'];
        $columns = (int)$atts['columns'];
        $custom_class = '';

        include $template;

        return ob_get_clean();
    }

    /**
     * Single product shortcode callback
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function product_shortcode($atts) {
        $atts = shortcode_atts(array(
            'slug' => '',
            'currency' => ''
        ), $atts, 'fourthwall_product');

        if (empty($atts['slug'])) {
            return '<p class="fourthwall-error">' . 
                   esc_html__('Please specify a product slug.', 'fourthwall-products') . 
                   '</p>';
        }

        $api_client = new Fourthwall_API_Client();
        $product = $api_client->get_product($atts['slug'], $atts['currency']);

        if (is_wp_error($product)) {
            return '<p class="fourthwall-error">' . esc_html($product->get_error_message()) . '</p>';
        }

        ob_start();

        // Convert single product to products array format for template compatibility
        $products = array(
            'results' => array($product)
        );

        $layout = 'grid';
        $columns = 1;
        $custom_class = 'fourthwall-single-product';

        // Load template
        $template = locate_template('fourthwall/widget-template.php');
        if (!$template) {
            $template = FOURTHWALL_PRODUCTS_PATH . 'templates/widget-template.php';
        }

        include $template;

        return ob_get_clean();
    }

    /**
     * Activate plugin
     */
    public function activate() {
        // Add default options
        add_option('fourthwall_storefront_token', '');
        add_option('fourthwall_default_currency', 'USD');
        add_option('fourthwall_cache_duration', 3600);

        // Clear rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Deactivate plugin
     */
    public function deactivate() {
        // Clear any plugin-specific transients
        $api_client = new Fourthwall_API_Client();
        $api_client->clear_cache();

        // Clear rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'fourthwall-products',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Add settings link to plugin list
     *
     * @param array $links Existing plugin action links
     * @return array
     */
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=fourthwall-products-settings'),
            __('Settings', 'fourthwall-products')
        );
        array_unshift($links, $settings_link);
        return $links;
    }
}

/**
 * Returns the main instance of Fourthwall_Products
 *
 * @return Fourthwall_Products
 */
function Fourthwall_Products() {
    return Fourthwall_Products::instance();
}

// Initialize the plugin
Fourthwall_Products();