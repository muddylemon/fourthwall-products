<?php
/**
 * Template for displaying Fourthwall products in widget
 *
 * This template can be overridden by copying it to yourtheme/fourthwall/widget-template.php
 *
 * @package FourthwallProducts
 * Variables available:
 * @var array  $products     Products data from API
 * @var string $layout       'grid' or 'list'
 * @var int    $columns      Number of columns for grid layout
 * @var string $custom_class Custom CSS class
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Set up classes based on layout
$wrapper_classes = array(
    'fourthwall-products',
    'fourthwall-products--' . $layout,
    'fourthwall-products--columns-' . $columns,
);

if ($custom_class) {
    $wrapper_classes[] = $custom_class;
}

if (empty($products['results'])) {
    echo '<p class="fourthwall-products-empty">' . 
        esc_html__('No products found.', 'fourthwall-products') . 
        '</p>';
    return;
}
?>

<div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>">
    <?php foreach ($products['results'] as $product) : ?>
        <?php
        // Skip products that aren't public or available
        if (!isset($product['access']['type']) || 
            $product['access']['type'] !== 'PUBLIC' || 
            !isset($product['state']['type']) || 
            $product['state']['type'] !== 'AVAILABLE') {
            continue;
        }

        // Get the first variant for pricing
        $variant = reset($product['variants']);
        if (!$variant) {
            continue;
        }

        // Get the primary product image
        $image = !empty($product['images']) ? reset($product['images']) : null;
        
        // Prepare price display
        $price = $variant['unitPrice'];
        $compare_price = $variant['compareAtPrice'] ?? null;
        ?>

        <div class="fourthwall-product">
            <a href="#" class="fourthwall-product__link" aria-label="<?php echo esc_attr($product['name']); ?>">
                <?php if ($image) : ?>
                    <div class="fourthwall-product__image-wrapper">
                        <img src="<?php echo esc_url($image['url']); ?>"
                             alt="<?php echo esc_attr($product['name']); ?>"
                             width="<?php echo esc_attr($image['width']); ?>"
                             height="<?php echo esc_attr($image['height']); ?>"
                             class="fourthwall-product__image"
                             loading="lazy">
                        
                        <?php if ($compare_price && $compare_price['value'] > $price['value']) : ?>
                            <span class="fourthwall-product__badge fourthwall-product__badge--sale">
                                <?php 
                                $discount = round((($compare_price['value'] - $price['value']) / $compare_price['value']) * 100);
                                printf(
                                    esc_html__('Save %d%%', 'fourthwall-products'),
                                    $discount
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="fourthwall-product__details">
                    <h3 class="fourthwall-product__title">
                        <?php echo esc_html($product['name']); ?>
                    </h3>

                    <?php if (!empty($product['description'])) : ?>
                        <div class="fourthwall-product__description">
                            <?php echo wp_kses_post($product['description']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="fourthwall-product__price">
                        <?php if ($compare_price && $compare_price['value'] > $price['value']) : ?>
                            <del class="fourthwall-product__price--compare">
                                <?php echo esc_html(number_format_i18n($compare_price['value'], 2)); ?>
                                <?php echo esc_html($compare_price['currency']); ?>
                            </del>
                        <?php endif; ?>

                        <span class="fourthwall-product__price--current">
                            <?php echo esc_html(number_format_i18n($price['value'], 2)); ?>
                            <?php echo esc_html($price['currency']); ?>
                        </span>
                    </div>

                    <?php if ($variant['stock']['type'] === 'LIMITED') : ?>
                        <div class="fourthwall-product__stock">
                            <?php if ($variant['stock']['inStock'] <= 5) : ?>
                                <span class="fourthwall-product__stock--low">
                                    <?php 
                                    printf(
                                        esc_html__('Only %d left in stock', 'fourthwall-products'),
                                        $variant['stock']['inStock']
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($variant['attributes'])) : ?>
                        <div class="fourthwall-product__variants">
                            <?php if (!empty($variant['attributes']['color'])) : ?>
                                <div class="fourthwall-product__color">
                                    <span class="fourthwall-product__color-swatch"
                                          style="background-color: <?php echo esc_attr($variant['attributes']['color']['swatch']); ?>"
                                          title="<?php echo esc_attr($variant['attributes']['color']['name']); ?>">
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($variant['attributes']['size'])) : ?>
                                <div class="fourthwall-product__size">
                                    <?php echo esc_html($variant['attributes']['size']['name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
        </div>
    <?php endforeach; ?>

    <?php if (isset($products['paging']) && $products['paging']['hasNextPage']) : ?>
        <div class="fourthwall-products__pagination">
            <button class="fourthwall-products__load-more">
                <?php esc_html_e('Load more products', 'fourthwall-products'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>