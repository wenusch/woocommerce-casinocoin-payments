<?php
/**
 * Plugin Name: WooCommerce CasinoCoin Payments
 * Plugin URI: https://github.com/wenusch/woocommerce-casinocoin-payments
 * Description: A payment gateway for WooCommerce to accept <a href="https://casinocoin.org/">CasinoCoin</a> payments.
 * Version: 1.0.0
 * Author: Massimo Wenusch
 * Author URI: https://github.com/wenusch
 * Text Domain: wc-gateway-csc
 * Domain Path: /languages/
 *
 * WC requires at least: 3.5.6
 * WC tested up to: 3.5.6
 *
 * License: ISC license
 */

/* If this file is called directly, abort. */
if (!defined('WPINC')) {
    die;
}

/* exit if no woocommerce is installed */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return false;
}

/* define constants */
define('WCCSC_VERSION', '1.0.0');
define('WCCSC_TEXTDOMAIN', 'wc-gateway-csc');
define('WCCSC_NAME', 'WooCommerce CasinoCoin Payments');
define('WCCSC_PLUGIN_ROOT', plugin_dir_path(__FILE__));
define('WCCSC_PLUGIN_ABSOLUTE', __FILE__);

if (!function_exists('woocommerce_csc_payment')) {
    /**
     * Unique access to instance of WC_Payment_CSC class
     *
     * @return \WC_Payment_CSC
     */
    function woocommerce_csc_payment()
    {
        // Load required classes and functions
        include_once dirname(__FILE__) . '/includes/class-wccsc-base.php';
        return WC_Payment_CSC::get_instance();
    }
}
if (!function_exists('wc_gateway_csc_constructor')) {
    function wc_gateway_csc_constructor()
    {
        /**
         * Load translations.
         */
        load_plugin_textdomain(
            'wc-gateway-csc',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
        woocommerce_csc_payment();
    }
}
add_action('plugins_loaded', 'wc_gateway_csc_constructor');

/**
 * Add custom meta_query so we can search by destination_tag.
 */
add_filter('woocommerce_order_data_store_cpt_get_orders_query', 'wc_gateway_csc_destination_tag_query', 10, 2);
function wc_gateway_csc_destination_tag_query($query, $query_vars)
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
add_action('woocommerce_thankyou', 'wc_gateway_csc_thankyou_payment_info', 10);
add_action('woocommerce_view_order', 'wc_gateway_csc_thankyou_payment_info', 10);
function wc_gateway_csc_thankyou_payment_info($order_id)
{
    $order = wc_get_order((int)$order_id);

    if ($order === false || $order->get_payment_method() !== 'csc') {
        return false;
    }

    $total     = (float)$order->get_meta('total_amount', true);
    $delivered = (float)$order->get_meta('delivered_amount', true);
    $remaining = round($total - $delivered, 6);

    include dirname(__FILE__) . '/views/thank_you.php';

    if ($order->get_status() === 'pending') {
        wp_enqueue_script(
            'wccsc-qrcode',
            plugins_url('/js/qrcodejs/qrcodejs.min.js', __FILE__),
            [
                'jquery'
            ]
        );
        wp_enqueue_script(
            'wccsc-ajax',
            plugins_url('/js/checkout.js', __FILE__),
            [
                'jquery'
            ]
        );
        wp_localize_script(
            'wccsc-ajax',
            'ajax_object',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'order_id' => $order->get_id()
            ]
        );
    }
}

/**
 * Handle the AJAX callback to reload checkout details.
 */
add_action('wp_ajax_csc_checkout', 'wc_gateway_csc_checkout_handler');
add_action('wp_ajax_nopriv_csc_checkout', 'wc_gateway_csc_checkout_handler');
function wc_gateway_csc_checkout_handler()
{
    $order = wc_get_order((int)$_POST['order_id']);

    if ($order === false) {
        header('HTTP/1.0 404 Not Found');
        wp_die();
    }

    $tag          = $order->get_meta('destination_tag', true);
    $csc_total    = round($order->get_meta('total_amount', true), 8);
    $csc_received = round($order->get_meta('delivered_amount', true), 8);
    $remaining    = round((float)$csc_total - (float)$csc_received, 8);
    $status       = $order->get_status();

    $result = [
        'csc_account'   => get_option('woocommerce_csc_settings')['csc_account'],
        'tag'           => $tag,
        'csc_total'     => $csc_total,
        'csc_received'  => $csc_received,
        'csc_remaining' => $remaining,
        'status'        => WC_Payment_CSC::get_instance()->helpers->wc_pretty_status($status),
        'raw_status'    => $status
    ];

    echo json_encode($result);
    wp_die();
}

/* add new order status */
function wc_gateway_csc_register_overpaid_order_status()
{
    register_post_status('wc-overpaid', [
        'label'                     => 'Overpaid',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Overpaid','Overpaid')
    ]);
}
add_action('init', 'wc_gateway_csc_register_overpaid_order_status');

/* Add to list of WC Order statuses */
function wc_gateway_csc_add_overpaid_to_order_statuses($order_statuses)
{
    $new_order_statuses = [];
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-overpaid'] = __('Overpaid', 'wc-gateway-csc');
        }
    }
    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'wc_gateway_csc_add_overpaid_to_order_statuses');

/* add color for new order status */
add_action('admin_head', 'wc_gateway_csc_styling_admin_order_list');
function wc_gateway_csc_styling_admin_order_list()
{
    global $pagenow, $post;

    if ($pagenow != 'edit.php') {
        return true;
    }
    if (get_post_type($post->ID) != 'shop_order') {
        return true;
    }
    $order_status = 'Overpaid';
    ?>
    <style>
        .order-status.status-<?php echo sanitize_title($order_status); ?> {
            background-color: #d7f8a7;
            color: #0c942b;
        }
    </style>
    <?php
}
