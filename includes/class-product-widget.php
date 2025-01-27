<?php
/**
 * Fourthwall Products Widget
 *
 * @package FourthwallProducts
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class Fourthwall_Products_Widget
 */
class Fourthwall_Products_Widget extends WP_Widget {
    /**
     * @var Fourthwall_API_Client
     */
    private $api_client;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'fourthwall_products_widget',
            __('Fourthwall Products', 'fourthwall-products'),
            array(
                'description' => __('Display Fourthwall products from a collection', 'fourthwall-products'),
                'classname' => 'fourthwall-products-widget',
            )
        );

        $this->api_client = new Fourthwall_API_Client();

        // Register widget styles
        add_action('wp_enqueue_scripts', array($this, 'register_widget_styles'));
    }

    /**
     * Register widget specific styles
     */
    public function register_widget_styles() {
        wp_register_style(
            'fourthwall-products-widget',
            plugins_url('assets/css/widget-styles.css', dirname(__FILE__)),
            array(),
            FOURTHWALL_PRODUCTS_VERSION
        );
    }

    /**
     * Widget frontend display
     *
     * @param array $args     Widget arguments
     * @param array $instance Saved values from database
     */
    public function widget($args, $instance) {
        wp_enqueue_style('fourthwall-products-widget');

        echo $args['before_widget'];

        // Display widget title if set
        if (!empty($instance['title'])) {
            echo $args['before_title'];
            echo esc_html($instance['title']);
            echo $args['after_title'];
        }

        // Get products from collection
        $products = $this->api_client->get_collection_products(
            $instance['collection_slug'],
            $instance['currency'],
            1,
            $instance['number_of_products']
        );

        if (is_wp_error($products)) {
            if (current_user_can('manage_options')) {
                echo '<p class="fourthwall-error">' . esc_html($products->get_error_message()) . '</p>';
            }
            echo $args['after_widget'];
            return;
        }

        // Load the template
        $template = locate_template('fourthwall/widget-template.php');
        if (!$template) {
            $template = plugin_dir_path(dirname(__FILE__)) . 'templates/widget-template.php';
        }

        // Set up template variables
        $layout = isset($instance['layout']) ? $instance['layout'] : 'grid';
        $columns = isset($instance['columns']) ? (int)$instance['columns'] : 2;
        $custom_class = isset($instance['custom_class']) ? $instance['custom_class'] : '';
        
        include $template;

        echo $args['after_widget'];
    }

    /**
     * Widget backend form
     *
     * @param array $instance Previously saved values from database
     */
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : '';
        $collection_slug = isset($instance['collection_slug']) ? $instance['collection_slug'] : '';
        $currency = isset($instance['currency']) ? $instance['currency'] : 'USD';
        $layout = isset($instance['layout']) ? $instance['layout'] : 'grid';
        $columns = isset($instance['columns']) ? (int)$instance['columns'] : 2;
        $number_of_products = isset($instance['number_of_products']) ? (int)$instance['number_of_products'] : 4;
        $custom_class = isset($instance['custom_class']) ? $instance['custom_class'] : '';

        // Get available collections for dropdown
        $collections = $this->api_client->get_collections();
        $currencies = $this->api_client->get_supported_currencies();
        ?>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'fourthwall-products'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('collection_slug')); ?>">
                <?php esc_html_e('Collection:', 'fourthwall-products'); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('collection_slug')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('collection_slug')); ?>">
                <option value=""><?php esc_html_e('Select a collection', 'fourthwall-products'); ?></option>
                <?php
                if (!is_wp_error($collections) && isset($collections['results'])) {
                    foreach ($collections['results'] as $collection) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($collection['slug']),
                            selected($collection_slug, $collection['slug'], false),
                            esc_html($collection['name'])
                        );
                    }
                }
                ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('currency')); ?>">
                <?php esc_html_e('Currency:', 'fourthwall-products'); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('currency')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('currency')); ?>">
                <?php
                foreach ($currencies as $code => $name) {
                    printf(
                        '<option value="%s" %s>%s (%s)</option>',
                        esc_attr($code),
                        selected($currency, $code, false),
                        esc_html($name),
                        esc_html($code)
                    );
                }
                ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('layout')); ?>">
                <?php esc_html_e('Layout:', 'fourthwall-products'); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('layout')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('layout')); ?>">
                <option value="grid" <?php selected($layout, 'grid'); ?>>
                    <?php esc_html_e('Grid', 'fourthwall-products'); ?>
                </option>
                <option value="list" <?php selected($layout, 'list'); ?>>
                    <?php esc_html_e('List', 'fourthwall-products'); ?>
                </option>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('columns')); ?>">
                <?php esc_html_e('Columns (Grid layout):', 'fourthwall-products'); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('columns')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('columns')); ?>">
                <?php
                for ($i = 1; $i <= 4; $i++) {
                    printf(
                        '<option value="%d" %s>%d</option>',
                        $i,
                        selected($columns, $i, false),
                        $i
                    );
                }
                ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('number_of_products')); ?>">
                <?php esc_html_e('Number of products:', 'fourthwall-products'); ?>
            </label>
            <input class="tiny-text" 
                   id="<?php echo esc_attr($this->get_field_id('number_of_products')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('number_of_products')); ?>" 
                   type="number" 
                   step="1" 
                   min="1" 
                   value="<?php echo esc_attr($number_of_products); ?>" 
                   size="3">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('custom_class')); ?>">
                <?php esc_html_e('Custom CSS Class:', 'fourthwall-products'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('custom_class')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('custom_class')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($custom_class); ?>">
        </p>
        <?php
    }

    /**
     * Processing widget options on save
     *
     * @param array $new_instance New values
     * @param array $old_instance Previously saved values from database
     *
     * @return array Updated safe values to be saved
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        $instance['title'] = (!empty($new_instance['title'])) 
            ? strip_tags($new_instance['title']) 
            : '';
            
        $instance['collection_slug'] = (!empty($new_instance['collection_slug'])) 
            ? sanitize_text_field($new_instance['collection_slug']) 
            : '';
            
        $instance['currency'] = (!empty($new_instance['currency'])) 
            ? sanitize_text_field($new_instance['currency']) 
            : 'USD';
            
        $instance['layout'] = (!empty($new_instance['layout'])) 
            ? sanitize_text_field($new_instance['layout']) 
            : 'grid';
            
        $instance['columns'] = (!empty($new_instance['columns'])) 
            ? absint($new_instance['columns']) 
            : 2;
            
        $instance['number_of_products'] = (!empty($new_instance['number_of_products'])) 
            ? absint($new_instance['number_of_products']) 
            : 4;
            
        $instance['custom_class'] = (!empty($new_instance['custom_class'])) 
            ? sanitize_html_class($new_instance['custom_class']) 
            : '';

        return $instance;
    }
}

// Register the widget
function register_fourthwall_products_widget() {
    register_widget('Fourthwall_Products_Widget');
}
add_action('widgets_init', 'register_fourthwall_products_widget');