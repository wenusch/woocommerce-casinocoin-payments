<?php
/**
 * Plugin Name: WooCommerce XRP
 * Plugin URI: http://github.com/empatogen/woocommerce-xrp
 * Description: A payment gateway for WooCommerce to accept <a href="https://ripple.com/xrp">XRP</a> payments.
 * Version: 1.0.3
 * Author: Jesper Wallin
 * Author URI: https://ifconfig.se/
 * Developer: Jesper Wallin
 * Developer URI: https://ifconfig.se/
 * Text Domain: wc-gateway-xrp
 * Domain Path: /languages/
 *
 * WC requires at least: 3.5.6
 * WC tested up to: 3.5.6
 *
 * Copyright: © 2019 Jesper Wallin.
 * License: ISC license
 */

/* If this file is called directly, abort. */
if ( !defined( 'WPINC' ) ) {
    die;
}

/* exit if no woocommerce is installed */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return false;
}

/* define constants */
define( 'WX_VERSION', '1.1.0' );
define( 'WX_TEXTDOMAIN', 'wc-gateway-xrp' );
define( 'WX_NAME', 'Woocommerce XRP' );
define( 'WX_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );
define( 'WX_PLUGIN_ABSOLUTE', __FILE__ );

if ( ! function_exists( 'woocommerce_xrp_payment' ) ) {
    /**
     * Unique access to instance of WC_Payment_XRP class
     *
     * @return \WC_Payment_XRP
     */
    function woocommerce_xrp_payment() {
        // Load required classes and functions
        include_once dirname(__FILE__) .'/includes/class-wcxrp-base.php';
        return WC_Payment_XRP::get_instance();
    }
}
if ( ! function_exists( 'wc_gateway_xrp_constructor' ) ) {
    function wc_gateway_xrp_constructor() {
        /**
         * Load translations.
         */
                load_plugin_textdomain( 'wc-gateway-xrp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
                woocommerce_xrp_payment();
            }
        }
        add_action( 'plugins_loaded', 'wc_gateway_xrp_constructor' );



/**
 * Add custom meta_query so we can search by destination_tag.
 */
add_filter('woocommerce_order_data_store_cpt_get_orders_query', 'wc_gateway_xrp_destination_tag_query', 10, 2);
function wc_gateway_xrp_destination_tag_query($query, $query_vars)
{
    if (!empty($query_vars['destination_tag'])) {
        $query['meta_query'][] = [
            'key' => 'destination_tag',
            'value' => esc_attr($query_vars['destination_tag']),
        ];
    }

    return $query;
}

/*
 * Customize the "thank you" page in order to display payment info.
 */
add_action('woocommerce_thankyou', 'wc_gateway_xrp_thankyou_payment_info', 10);
add_action('woocommerce_view_order', 'wc_gateway_xrp_thankyou_payment_info', 10);
function wc_gateway_xrp_thankyou_payment_info($order_id)
{
    if (get_post_meta($order_id, '_payment_method', true) !== 'xrp') {
        return false;
    }
    $gateway    = new WC_Gateway_XRP();
    $total     = (float)get_post_meta($order_id, 'total_amount', true);
    $delivered = (float)get_post_meta($order_id, 'delivered_amount', true);
    $remaining = round($total - $delivered, 6);
?>
    <h2><?php _e('XRP payment details', 'wc-gateway-xrp'); ?></h2>
    <div id="wc_xrp_qrcode"></div>
    <table class="woocommerce-table shop_table xrp_info">
        <tbody>
            <tr>
                <th><?php _e('XRP Account', 'wc-gateway-xrp'); ?></th>
                <td id="xrp_account"><?php _e($gateway->settings['xrp_account'] , 'wc-gateway-xrp') ?></td>
            </tr>
            <tr>
                <th><?php _e('Destination tag', 'wc-gateway-xrp'); ?></th>
                <td id="destination_tag"><?php echo get_post_meta($order_id, 'destination_tag', true) ?></td>
            </tr>
            <tr>
                <th><?php _e('XRP total', 'wc-gateway-xrp'); ?></th>
                <td id="xrp_total"><?php echo round(get_post_meta($order_id, 'total_amount', true), 6) ?></td>
            </tr>
            <tr>
                <th><?php _e('XRP received', 'wc-gateway-xrp'); ?></th>
                <td id="xrp_received"><?php echo round(get_post_meta($order_id, 'delivered_amount', true), 6) ?></td>
            </tr>
            <tr>
                <th><?php _e('XRP left to pay', 'wc-gateway-xrp'); ?></th>
                <td id="xrp_remaining"><?php echo $remaining ?></td>
            </tr>
            <tr>
                <th><?php _e('Order status', 'wc-gateway-xrp'); ?></th>
                <td id="xrp_status"><?php echo $gateway->helpers->wc_pretty_status(get_post_status($order_id)) ?></td>
            </tr>
        </tbody>
    </table>
    <?php

    if (get_post_status($order_id) === 'wc-pending') {
        wp_enqueue_script(
            'wcxrp-qrcode',
            plugins_url('/js/qrcodejs/qrcodejs.min.js', __FILE__),
            [
                'jquery'
            ]
        );
        wp_enqueue_script(
            'wcxrp-ajax',
            plugins_url('/js/checkout.js', __FILE__),
            [
                'jquery'
            ]
        );
        wp_localize_script(
            'wcxrp-ajax',
            'ajax_object',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'order_id' => $order_id
            ]
        );
    }
}

/**
 * Handle the AJAX callback to reload checkout details.
 */
add_action('wp_ajax_xrp_checkout', 'wc_gateway_xrp_checkout_handler');
add_action('wp_ajax_nopriv_xrp_checkout', 'wc_gateway_xrp_checkout_handler');
function wc_gateway_xrp_checkout_handler()
{
    $order = wc_get_order($_POST['order_id']);

    if ($order === false) {
        header('HTTP/1.0 404 Not Found');
        wp_die();
    }

    $gateway      = new WC_Gateway_XRP();
    $tag          = get_post_meta($_POST['order_id'], 'destination_tag', true);
    $xrp_total    = round(get_post_meta($_POST['order_id'], 'total_amount', true), 6);
    $xrp_received = round(get_post_meta($_POST['order_id'], 'delivered_amount', true), 6);
    $remaining    = round((float)$xrp_total - (float)$xrp_received, 6);
    $status       = get_post_status($_POST['order_id']);

    $result = [
        'xrp_account'   => $gateway->settings['xrp_account'],
        'tag'           => $tag,
        'xrp_total'     => $xrp_total,
        'xrp_received'  => $xrp_received,
        'xrp_remaining' => $remaining,
        'status'        => $gateway->helpers->wc_pretty_status($status),
        'raw_status'    => $status
    ];

    echo json_encode($result);
    wp_die();
}
