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

// Add the Social Civic Number and Organization Number fields only for specific product categories
add_filter('woocommerce_checkout_fields', 'conditionally_add_social_civic_and_org_number_fields');
function conditionally_add_social_civic_and_org_number_fields($fields) {
    $categories_civic = array('mobilabonnemang', 'studentabonnemang', 'familjeabonnemang', 'foretagsabonnemang');
    $categories_org = array('foretagsabonnemang');
    $add_civic = false;
    $add_org = false;
    
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));
        
        if (array_intersect($categories_civic, $product_categories)) {
            $add_civic = true;
        }
        if (array_intersect($categories_org, $product_categories)) {
            $add_org = true;
        }
    }
    
    if ($add_civic) {
        $saved_civic_value = WC()->session->get('social_civic_number', '');
        $fields['billing']['social_civic_number'] = array(
            'type'        => 'text',
            'class'       => array('form-row-wide'),
            'label'       => __('Personnummer'),
            'placeholder' => 'ÅÅÅÅMMDDXXXX',
            'required'    => true,
            'priority'    => 5,
            'default'     => $saved_civic_value,
        );
    }
    
    if ($add_org) {
        $saved_org_value = WC()->session->get('organization_number', '');
        $fields['billing']['organization_number'] = array(
            'type'        => 'text',
            'class'       => array('form-row-wide'),
            'label'       => __('Organisationsnummer'),
            'placeholder' => 'ÅÅÅÅMMDDXXXX',
            'required'    => true,
            'priority'    => 6,
            'default'     => $saved_org_value,
        );
    }
    
    return $fields;
}

// Validate the Social Civic Number and Organization Number format
add_action('woocommerce_checkout_process', 'validate_social_civic_and_org_number');
function validate_social_civic_and_org_number() {
    if (isset($_POST['social_civic_number']) && !preg_match('/^\d{8}-\d{4}$|^\d{12}$/', $_POST['social_civic_number'])) {
        wc_add_notice(__('Fyll i ett tolvsiffrigt personnummer enligt formatet ÅÅÅÅMMDDXXXX.'), 'error');
    }
    if (isset($_POST['organization_number']) && !preg_match('/^\d{8}-\d{4}$|^\d{12}$/', $_POST['organization_number'])) {
        wc_add_notice(__('Fyll i ett korrekt organisationsnummer enligt formatet ÅÅÅÅMMDDXXXX.'), 'error');
    }
}

// Save the Social Civic Number and Organization Number to order meta
add_action('woocommerce_checkout_update_order_meta', 'save_social_civic_and_org_number');
function save_social_civic_and_org_number($order_id) {
    if (!empty($_POST['social_civic_number'])) {
        update_post_meta($order_id, '_social_civic_number', preg_replace('/[^0-9]/', '', $_POST['social_civic_number']));
    }
    if (!empty($_POST['organization_number'])) {
        update_post_meta($order_id, '_organization_number', preg_replace('/[^0-9]/', '', $_POST['organization_number']));
    }
}

// Display Social Civic Number and Organization Number in the admin order panel
add_action('woocommerce_admin_order_data_after_billing_address', 'display_social_civic_and_org_number_admin', 10, 1);
function display_social_civic_and_org_number_admin($order) {
    $social_number = get_post_meta($order->get_id(), '_social_civic_number', true);
    $org_number = get_post_meta($order->get_id(), '_organization_number', true);
    if ($social_number) {
        echo '<p><strong>' . __('Personnummer') . ':</strong> ' . esc_html($social_number) . '</p>';
    }
    if ($org_number) {
        echo '<p><strong>' . __('Organisationsnummer') . ':</strong> ' . esc_html($org_number) . '</p>';
    }
}

// Display Social Civic Number and Organization Number on the Thank You page
add_action('woocommerce_thankyou', 'display_social_civic_and_org_number_thankyou', 5);
function display_social_civic_and_org_number_thankyou($order_id) {
    $social_number = get_post_meta($order_id, '_social_civic_number', true);
    $org_number = get_post_meta($order_id, '_organization_number', true);
    if ($social_number || $org_number) {
        ?>
        <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details social-civic-number">
            <?php if ($social_number) { ?>
                <li class="woocommerce-order-overview__order order">
                    <?php esc_html_e('Personnummer:', 'woocommerce'); ?>
                    <strong><?php echo esc_html($social_number); ?></strong>
                </li>
            <?php } ?>
            <?php if ($org_number) { ?>
                <li class="woocommerce-order-overview__order order">
                    <?php esc_html_e('Organisationsnummer:', 'woocommerce'); ?>
                    <strong><?php echo esc_html($org_number); ?></strong>
                </li>
            <?php } ?>
        </ul>
        <?php
    }
}

// Add Social Civic Number and Organization Number to WooCommerce confirmation emails
add_action('woocommerce_email_order_meta', 'add_social_civic_and_org_number_to_email', 10, 3);
function add_social_civic_and_org_number_to_email($order, $sent_to_admin, $plain_text) {
    $social_number = get_post_meta($order->get_id(), '_social_civic_number', true);
    $org_number = get_post_meta($order->get_id(), '_organization_number', true);
    if ($social_number) {
        echo '<p><strong>' . __('Personnummer') . ':</strong> ' . esc_html($social_number) . '</p>';
    }
    if ($org_number) {
        echo '<p><strong>' . __('Organisationsnummer') . ':</strong> ' . esc_html($org_number) . '</p>';
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