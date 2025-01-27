<?php
/**
 * Fourthwall Products Settings
 *
 * @package FourthwallProducts
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class Fourthwall_Settings
 */
class Fourthwall_Settings {
    /**
     * Option group
     *
     * @var string
     */
    private $option_group = 'fourthwall_products';

    /**
     * Settings page slug
     *
     * @var string
     */
    private $page_slug = 'fourthwall-products-settings';

    /**
     * API Client instance
     *
     * @var Fourthwall_API_Client
     */
    private $api_client;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_client = new Fourthwall_API_Client();
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_options_page(
            __('Fourthwall Products Settings', 'fourthwall-products'),
            __('Fourthwall Products', 'fourthwall-products'),
            'manage_options',
            $this->page_slug,
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_' . $this->page_slug !== $hook) {
            return;
        }

        wp_enqueue_style(
            'fourthwall-admin',
            plugins_url('assets/css/admin-settings.css', dirname(__FILE__)),
            array(),
            FOURTHWALL_PRODUCTS_VERSION
        );

        wp_enqueue_script(
            'fourthwall-admin',
            plugins_url('assets/js/admin-settings.js', dirname(__FILE__)),
            array('jquery'),
            FOURTHWALL_PRODUCTS_VERSION,
            true
        );

        wp_localize_script('fourthwall-admin', 'fourthwallAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fourthwall_admin'),
            'strings' => array(
                'testSuccess' => __('Connection successful!', 'fourthwall-products'),
                'testError' => __('Connection failed:', 'fourthwall-products'),
            ),
        ));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            $this->option_group,
            'fourthwall_storefront_token',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );

        register_setting(
            $this->option_group,
            'fourthwall_default_currency',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_currency'),
                'default' => 'USD',
            )
        );

        register_setting(
            $this->option_group,
            'fourthwall_cache_duration',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 3600,
            )
        );

        // Settings sections
        add_settings_section(
            'fourthwall_api_settings',
            __('API Settings', 'fourthwall-products'),
            array($this, 'render_api_section'),
            $this->page_slug
        );

        add_settings_section(
            'fourthwall_display_settings',
            __('Display Settings', 'fourthwall-products'),
            array($this, 'render_display_section'),
            $this->page_slug
        );

        // API Settings fields
        add_settings_field(
            'fourthwall_storefront_token',
            __('Storefront Token', 'fourthwall-products'),
            array($this, 'render_token_field'),
            $this->page_slug,
            'fourthwall_api_settings'
        );

        // Display Settings fields
        add_settings_field(
            'fourthwall_default_currency',
            __('Default Currency', 'fourthwall-products'),
            array($this, 'render_currency_field'),
            $this->page_slug,
            'fourthwall_display_settings'
        );

        add_settings_field(
            'fourthwall_cache_duration',
            __('Cache Duration (seconds)', 'fourthwall-products'),
            array($this, 'render_cache_duration_field'),
            $this->page_slug,
            'fourthwall_display_settings'
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'fourthwall_messages',
                'fourthwall_message',
                __('Settings Saved', 'fourthwall-products'),
                'updated'
            );
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('fourthwall_messages'); ?>

            <form action="options.php" method="post">
                <?php
                settings_fields($this->option_group);
                do_settings_sections($this->page_slug);
                submit_button();
                ?>
            </form>

            <div class="fourthwall-tools">
                <h2><?php esc_html_e('Tools', 'fourthwall-products'); ?></h2>
                
                <div class="fourthwall-tool-section">
                    <button type="button" 
                            class="button" 
                            id="fourthwall-test-connection">
                        <?php esc_html_e('Test API Connection', 'fourthwall-products'); ?>
                    </button>
                    <span class="spinner"></span>
                    <span class="fourthwall-test-result"></span>
                </div>

                <div class="fourthwall-tool-section">
                    <button type="button" 
                            class="button" 
                            id="fourthwall-clear-cache">
                        <?php esc_html_e('Clear Cache', 'fourthwall-products'); ?>
                    </button>
                    <span class="spinner"></span>
                    <span class="fourthwall-cache-result"></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render API settings section
     */
    public function render_api_section() {
        echo '<p>';
        esc_html_e(
            'Configure your Fourthwall API settings. You can find your storefront token in your Fourthwall dashboard.',
            'fourthwall-products'
        );
        echo '</p>';
    }

    /**
     * Render display settings section
     */
    public function render_display_section() {
        echo '<p>';
        esc_html_e(
            'Configure how products are displayed on your site.',
            'fourthwall-products'
        );
        echo '</p>';
    }

    /**
     * Render token field
     */
    public function render_token_field() {
        $token = get_option('fourthwall_storefront_token');
        ?>
        <input type="text"
               name="fourthwall_storefront_token"
               id="fourthwall_storefront_token"
               value="<?php echo esc_attr($token); ?>"
               class="regular-text"
               autocomplete="off"
               placeholder="ptkn_xxxxxxxxxxxxxxxxxx">
        <p class="description">
            <?php esc_html_e('Enter your Fourthwall storefront token', 'fourthwall-products'); ?>
        </p>
        <?php
    }

    /**
     * Render currency field
     */
    public function render_currency_field() {
        $currency = get_option('fourthwall_default_currency', 'USD');
        $currencies = $this->api_client->get_supported_currencies();
        ?>
        <select name="fourthwall_default_currency" id="fourthwall_default_currency">
            <?php foreach ($currencies as $code => $name) : ?>
                <option value="<?php echo esc_attr($code); ?>" 
                        <?php selected($currency, $code); ?>>
                    <?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Select the default currency for product prices', 'fourthwall-products'); ?>
        </p>
        <?php
    }

    /**
     * Render cache duration field
     */
    public function render_cache_duration_field() {
        $duration = get_option('fourthwall_cache_duration', 3600);
        ?>
        <input type="number"
               name="fourthwall_cache_duration"
               id="fourthwall_cache_duration"
               value="<?php echo esc_attr($duration); ?>"
               class="small-text"
               min="60"
               step="60">
        <p class="description">
            <?php esc_html_e('How long to cache API responses (in seconds)', 'fourthwall-products'); ?>
        </p>
        <?php
    }

    /**
     * Sanitize currency
     *
     * @param string $currency Currency code
     * @return string
     */
    public function sanitize_currency($currency) {
        $currencies = array_keys($this->api_client->get_supported_currencies());
        return in_array($currency, $currencies) ? $currency : 'USD';
    }

    /**
     * Clear cache ajax handler
     */
    public function ajax_clear_cache() {
        check_ajax_referer('fourthwall_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'fourthwall-products'));
        }

        $this->api_client->clear_cache();
        wp_send_json_success(__('Cache cleared successfully', 'fourthwall-products'));
    }

    /**
     * Test connection ajax handler
     */
    public function ajax_test_connection() {
        check_ajax_referer('fourthwall_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'fourthwall-products'));
        }

        $collections = $this->api_client->get_collections();
        
        if (is_wp_error($collections)) {
            wp_send_json_error($collections->get_error_message());
        }

        wp_send_json_success(__('Connection successful', 'fourthwall-products'));
    }
}

// Initialize settings
add_action('init', function() {
    $settings = new Fourthwall_Settings();
    
    // Register AJAX handlers
    add_action('wp_ajax_fourthwall_clear_cache', array($settings, 'ajax_clear_cache'));
    add_action('wp_ajax_fourthwall_test_connection', array($settings, 'ajax_test_connection'));
});