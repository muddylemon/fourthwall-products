<?php
/**
 * Fourthwall API Client
 *
 * @package FourthwallProducts
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class Fourthwall_API_Client
 * 
 * Handles all API interactions with the Fourthwall Storefront API
 */
class Fourthwall_API_Client
{
    /**
     * API base URL
     *
     * @var string
     */
    private $base_url = 'https://storefront-api.fourthwall.com';

    /**
     * Default currency
     *
     * @var string
     */
    private $default_currency = 'USD';

    /**
     * Cache duration in seconds (default: 1 hour)
     *
     * @var int
     */
    private $cache_duration = 3600;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cache_duration = get_option('fourthwall_cache_duration', 3600);
    }

    /**
     * Get the storefront token from WordPress options
     *
     * @return string|bool
     */
    private function get_storefront_token()
    {
        return get_option('fourthwall_storefront_token');
    }

    /**
     * Make an API request
     *
     * @param string $endpoint The API endpoint
     * @param array  $params   Query parameters
     * @return array|WP_Error
     */
    private function make_request($endpoint, $params = [])
    {
        $token = $this->get_storefront_token();

        if (!$token) {
            return new WP_Error('no_token', __('Storefront token is not configured', 'fourthwall-products'));
        }

        // Add token to params if not already present
        $params['storefront_token'] = $token;

        // Build URL with proper encoding
        $url = add_query_arg(
            array_map('urlencode', $params),
            $this->base_url . $endpoint
        );

        // Make request with additional headers
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress/Fourthwall-Products-Plugin',
                'Authorization' => 'Bearer ' . $token // Try adding Bearer authentication
            ],
            'sslverify' => true // Ensure SSL verification is enabled
        ]);

        // Enhanced error logging
        if (is_wp_error($response)) {
            error_log('Fourthwall API Error: ' . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Log detailed response information for debugging
        error_log(sprintf(
            'Fourthwall API Response [%s]: Code: %d, Body: %s',
            $url,
            $response_code,
            $body
        ));

        if ($response_code !== 200) {
            return new WP_Error(
                'api_error',
                sprintf(
                    __('API request failed with status %d: %s', 'fourthwall-products'),
                    $response_code,
                    $body
                )
            );
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Failed to parse API response', 'fourthwall-products'));
        }

        return $data;
    }
    /**
     * Get all collections
     *
     * @return array|WP_Error
     */
    public function get_collections()
    {
        $cache_key = 'fourthwall_collections';
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->make_request('/v1/collections');

        if (!is_wp_error($response)) {
            set_transient($cache_key, $response, $this->cache_duration);
        }

        return $response;
    }

    /**
     * Get a specific collection by slug
     *
     * @param string $slug Collection slug
     * @return array|WP_Error
     */
    public function get_collection($slug)
    {
        $cache_key = 'fourthwall_collection_' . $slug;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->make_request("/v1/collections/{$slug}");

        if (!is_wp_error($response)) {
            set_transient($cache_key, $response, $this->cache_duration);
        }

        return $response;
    }

    /**
     * Get products in a collection
     *
     * @param string $slug     Collection slug
     * @param string $currency Currency code
     * @param int    $page     Page number
     * @param int    $size     Page size
     * @return array|WP_Error
     */
    public function get_collection_products($slug, $currency = null, $page = 1, $size = 20)
    {
        $params = [
            'currency' => $currency ?: $this->default_currency,
            'page' => $page,
            'size' => $size,
        ];

        $cache_key = sprintf(
            'fourthwall_collection_products_%s_%s_%d_%d',
            $slug,
            $params['currency'],
            $page,
            $size
        );

        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->make_request("/v1/collections/{$slug}/products", $params);

        if (!is_wp_error($response)) {
            set_transient($cache_key, $response, $this->cache_duration);
        }

        return $response;
    }

    /**
     * Get a specific product by slug
     *
     * @param string $slug     Product slug
     * @param string $currency Currency code
     * @return array|WP_Error
     */
    public function get_product($slug, $currency = null)
    {
        $params = [];
        if ($currency) {
            $params['currency'] = $currency;
        }

        $cache_key = 'fourthwall_product_' . $slug . '_' . ($currency ?: $this->default_currency);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->make_request("/v1/products/{$slug}", $params);

        if (!is_wp_error($response)) {
            set_transient($cache_key, $response, $this->cache_duration);
        }

        return $response;
    }

    /**
     * Clear all API cache
     *
     * @return void
     */
    public function clear_cache()
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like('_transient_fourthwall_') . '%',
                $wpdb->esc_like('_transient_timeout_fourthwall_') . '%'
            )
        );
    }

    /**
     * Get supported currencies
     *
     * @return array
     */
    public function get_supported_currencies()
    {
        return [
            'USD' => __('US Dollar', 'fourthwall-products'),
            'EUR' => __('Euro', 'fourthwall-products'),
            'CAD' => __('Canadian Dollar', 'fourthwall-products'),
            'GBP' => __('British Pound', 'fourthwall-products'),
            'AUD' => __('Australian Dollar', 'fourthwall-products'),
            'NZD' => __('New Zealand Dollar', 'fourthwall-products'),
            'SEK' => __('Swedish Krona', 'fourthwall-products'),
            'NOK' => __('Norwegian Krone', 'fourthwall-products'),
            'DKK' => __('Danish Krone', 'fourthwall-products'),
            'PLN' => __('Polish Złoty', 'fourthwall-products'),
            'INR' => __('Indian Rupee', 'fourthwall-products'),
            'JPY' => __('Japanese Yen', 'fourthwall-products'),
            'MYR' => __('Malaysian Ringgit', 'fourthwall-products'),
            'SGD' => __('Singapore Dollar', 'fourthwall-products'),
        ];
    }
}