<?php
/*
Plugin Name: Social Civic Number Custom Field Wp Plugin
Plugin URI: https://koderise.com/
Description: Adds a mandatory Social Civic Number field for smartphone subscription products in WooCommerce checkout.
Version: 1.0
Author: Kaushal Gajjar
Author URI: https://gajjarkaushal.com/
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


// Add the Social Civic Number field at the beginning of the billing form for smartphone subscriptions
add_filter('woocommerce_checkout_fields', 'add_social_civic_number_field_first');
function add_social_civic_number_field_first($fields) {
    $saved_value = WC()->session->get('social_civic_number', '');
    $social_civic_field = array(
        'social_civic_number' => array(
            'type'        => 'text',
            'class'       => array('form-row-wide'),
            'label'       => __('Personnummer (ÅÅÅÅMMDDXXXX)'),
            'placeholder' => '19900101-1234 or 199001011234',
            'required'    => true,
            'priority'    => 5, // Ensures it appears first
            'default'     => $saved_value,
        )
    );
    
    $fields['billing'] = array_merge($social_civic_field, $fields['billing']);
    
    return $fields;
}

// Validate the Social Civic Number format
add_action('woocommerce_checkout_process', 'validate_social_civic_number');
function validate_social_civic_number() {
    if (!isset($_POST['social_civic_number'])) return;

    $social_number = $_POST['social_civic_number'];
    if (!preg_match('/^\d{8}-\d{4}$|^\d{12}$/', $social_number)) {
        wc_add_notice(__('Fyll i ett tolvsiffrigt personnummer enligt formatet ÅÅÅÅMMDDXXXX.'), 'error');
    }
}

// Save the Social Civic Number to order meta
add_action('woocommerce_checkout_update_order_meta', 'save_social_civic_number');
function save_social_civic_number($order_id) {
    if (!empty($_POST['social_civic_number'])) {
        $social_number = preg_replace('/[^0-9]/', '', $_POST['social_civic_number']);
        update_post_meta($order_id, '_social_civic_number', $social_number);
    }
}

// Display Social Civic Number in the admin order panel
add_action('woocommerce_admin_order_data_after_billing_address', 'display_social_civic_number_admin', 10, 1);
function display_social_civic_number_admin($order) {
    $social_number = get_post_meta($order->get_id(), '_social_civic_number', true);
    if ($social_number) {
        echo '<p><strong>' . __('Personnummer') . ':</strong> ' . esc_html($social_number) . '</p>';
    }
}

add_action('woocommerce_thankyou', 'display_social_civic_number_thankyou', 5);
function display_social_civic_number_thankyou($order_id) {
    $social_number = get_post_meta($order_id, '_social_civic_number', true);
    if ($social_number) {
        ?>
        	<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details social-civic-number">
                <li class="woocommerce-order-overview__order order">
                    <?php esc_html_e( 'Personnummer:', 'woocommerce' ); ?>
                    <strong><?php echo esc_html($social_number); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
                </li>
            </ul>
        <?php
    }
}

// Add custom CSS to modify WooCommerce Thank You page order details
add_action('wp_enqueue_scripts', 'custom_checkout_styles');
function custom_checkout_styles() {
    wp_enqueue_style('custom-checkout-css', plugin_dir_url(__FILE__) . 'custom-checkout.css');
}

// Add Social Civic Number to WooCommerce confirmation emails
add_action('woocommerce_email_order_meta', 'add_social_civic_number_to_email', 10, 3);
function add_social_civic_number_to_email($order, $sent_to_admin, $plain_text) {
    $social_number = get_post_meta($order->get_id(), '_social_civic_number', true);
    if ($social_number) {
        echo '<p><strong>' . __('Social Civic Number') . ':</strong> ' . esc_html($social_number) . '</p>';
    }
}

// add_filter('woocommerce_mail_content', 'add_social_civic_number_to_email_prints', 10, 3);
function add_social_civic_number_to_email_prints( $message ){
    echo $message;
    die;
    return $message;
}
function custom_remove_shipping_charge_if_categories_present($rates, $package) {
    // List of category slugs that should trigger free shipping
    $target_categories = array(
        'mobilabonnemang',
        'studentabonnemang',
        'familjeabonnemang',
        'foretagsabonnemang'
    );

    $cart_has_target_category = false;

    // Loop through cart items
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));

        // Check if any of the product categories are in the target list
        if (array_intersect($target_categories, $product_categories)) {
            $cart_has_target_category = true;
            break;
        }
    }

    // If any of the target categories exist in the cart, set shipping cost to 0
    if ($cart_has_target_category) {
        foreach ($rates as $rate_key => $rate) {
            $rates[$rate_key]->cost = 0;
            if (isset($rates[$rate_key]->taxes)) {
                $rates[$rate_key]->taxes = array_map(fn($tax) => 0, $rates[$rate_key]->taxes);
            }
        }
    }

    return $rates;
}
add_filter('woocommerce_package_rates', 'custom_remove_shipping_charge_if_categories_present', 10, 2);

// // Force cart total recalculation when updating shipping cost
// function force_cart_total_recalculation() {
//     WC()->cart->calculate_totals();
// }
// add_action('woocommerce_before_calculate_totals', 'force_cart_total_recalculation', 10);