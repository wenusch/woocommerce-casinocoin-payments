<?php
/**
 * Plugin Name: WooCommerce XRP
 * Plugin URI: http://github.com/empatogen/woocommerce-xrp
 * Description: Your extension's description text.
 * Version: 1.0.0
 * Author: Jesper Wallin
 * Author URI: https://ifconfig.se/
 * Developer: Jesper Wallin
 * Developer URI: https://ifconfig.se/
 *
 * WC requires at least: 3.5.6
 * WC tested up to: 3.5.6
 *
 * Copyright: © 2019 Jesper Wallin.
 * License: ISC license
 */

defined( 'ABSPATH' ) or die( 'Nothing to see here' );

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}


/**
 * XRP Payment Gateway
 *
 * Provides an Offline Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class       WC_Gateway_XRP
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce/Classes/Payment
 * @author      Jesper Wallin
 */
add_action( 'plugins_loaded', 'wc_gateway_xrp_init', 11 );

function wc_gateway_xrp_init() {

    class WC_Gateway_XRP extends WC_Payment_Gateway {

        public function __construct() {
            $this->id                    = 'xrp';
            $this->has_fields            = false;
            $this->method_title          = __( 'XRP', 'wc-gateway-xrp' );
            $this->method_description    = __( 'Let your customers pay using the XRP Ledger', 'wc-gateway-xrp' );

            $this->title                 = $this->get_option('title');
            $this->description           = $this->get_option('description');
            $this->xrp_account           = $this->get_option('xrp_account');
            $this->xrpl_webhook_api_pub  = $this->get_option('xrpl_webhook_api_pub');
            $this->xrpl_webhook_api_priv = $this->get_option('xrpl_webhook_api_priv');
            $this->xrp_node              = $this->get_option('xrp_node');
            $this->tx_limit              = $this->get_option('tx_limit');

            $this->init_form_fields();
            $this->init_settings();

            if ( ! in_array( get_woocommerce_currency(), array( 'EUR', 'USD' ) ) ) {
                add_action( 'admin_notices', array( $this, 'supported_currencies' ) );
            }
            add_action( 'woocommerce_api_wc_gateway_xrp', array( $this, 'check_ledger' ) );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }


        /**
         * Display an error that the current currency is unsupported.
         */
        public function supported_currencies() {
            printf(
                '<div class="notice notice-error"><p>Your current currency (<b>%s</b>) is not supported yet. Please use <b>USD</b> or <b>EUR</b> with for now.</p></div>',
                get_woocommerce_currency()
            );
        }


        /**
         * Display an error that all XRP related data is required.
         *
         * Unfortunately, a "Your settings has been saved" message is shown as well and
         * can't really be removed without lots of ugly hacks.
         */
        public function require_xrp() {
            echo '<div class="notice notice-error"><p>You <b>need</b> to specify your <b>XRP Account</b> and <b>XRPL Webhook</b> details before you can use this payment gateway.</p></div>';
        }

        /**
         * Process admin options and setup hooks.
         */
        public function process_admin_options() {
            if ( $this->has_xrp_data() === false ) {
                add_action( 'admin_notices', array( $this, 'require_xrp' ) );
                return false;
            }

            $saved = parent::process_admin_options();

            if ($this->enabled == 'yes') {
                $this->setup_webhooks();
            }

            return $saved;
        }


        /**
         * Setup our webhook and subscriptions
         */
        public function setup_webhooks() {
            include_once dirname( __FILE__ ) . '/includes/class-webhooks.php';
            $wh = new Webhook( $this->get_option( 'xrpl_webhook_api_pub' ), $this->get_option( 'xrpl_webhook_api_priv' ) );

            /* subscriptions */
            $subs = $wh->subscriptions();
            $exists = false;
            foreach ($subs as $sub) {
                if ($sub->address == $this->get_option( 'xrp_account' ) ) {
                    $exists = true;
                    break;
                }
            }
            if ($exists == false) {
                $wh->add_subscription( $this->get_option( 'xrp_account' ) );
            }

            /* webhooks */
            $url = WC()->api_request_url( 'WC_Gateway_XRP' );
            $hooks = $wh->webhooks();
            $exists = false;
            foreach ($hooks as $hook) {
                if ($hook->url == $url) {
                    $exists = true;
                    break;
                }
            }
            if ($exists == false) {
                $wh->add_webhook( $url );
            }

            return true;
        }


        /**
         * Return our XRP account.
         */
        public function get_xrp_account() {
            return $this->xrp_account;
        }


        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields() {

            $this->form_fields = apply_filters( 'wc_xrp_form_fields', array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'wc-gateway-xrp' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable XRP Payments', 'wc-gateway-xrp' ),
                    'default' => 'no'
                ),

                'title' => array(
                    'title'       => __( 'Title', 'wc-gateway-xrp' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-xrp' ),
                    'default'     => __( 'XRP', 'wc-gateway-xrp' ),
                    'desc_tip'    => true,
                ),

                'description' => array(
                    'title'       => __( 'Description', 'wc-gateway-xrp' ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-xrp' ),
                    'default'     => __( 'Pay using the XRP ledger.', 'wc-gateway-xrp' ),
                    'desc_tip'    => true,
                ),

                'xrp' => array(
                    'title'       => __( 'XRP Account', 'wc-gateway-xrp' ),
                    'type'        => 'title',
                    'description' => 'Please specify the XRP Ledger account where your payments should be sent.'
                ),

                'xrp_account' => array(
                    'title'       => __( 'XRP Account', 'wc-gateway-xrp' ),
                    'type'        => 'text',
                    'description' => __( 'Your XRP account where payments should be sent.', 'wc-gateway-xrp' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),

                'xrpl_webhook' => array(
                    'title'       => __( 'XRPL Webhook options', 'wc-gateway-xrp' ),
                    'type'        => 'title',
                    'description' => 'In order to create your webhook and process your payments properly, please specify your XRPL Webhooks API key. For more informations how to obtain these keys, please visit <a href="https://webhook.xrpayments.co/">https://webhook.xrpayments.co</a>.',
                ),

                'xrpl_webhook_api_pub' => array(
                    'title'       => __( 'API Key', 'wc-gateway-xrp' ),
                    'type'        => 'text',
                    'description' => __( 'Your XRPL XRPayments Webhook API key.', 'wc-gateway-xrp' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),

                'xrpl_webhook_api_priv' => array(
                    'title'       => __( 'API Secret', 'wc-gateway-xrp' ),
                    'type'        => 'text',
                    'description' => __( 'Your XRPL XRPayments Webhook API secret.', 'wc-gateway-xrp' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),

                'advanced' => array(
                    'title'       => __( 'Advanced', 'wc-gateway-xrp' ),
                    'type'        => 'title',
                    'description' => 'Leave these untouched unless you really know what you\'re doing.',
                ),

                'xrp_node' => array(
                    'title'       => __( 'XRP Node', 'wc-gateway-xrp' ),
                    'type'        => 'text',
                    'description' => 'Which XRP node to use when checking our balance.',
                    'default'     => 'https://s2.ripple.com:51234',
                ),

                'tx_limit' => array(
                    'title'       => __( 'Transaction Limit', 'wc-gateway-xrp' ),
                    'type'        => 'number',
                    'description' => 'The number of transactions to fetch from the ledger each time we check for new payments.',
                    'default'     => 10,
                ),

            ) );

        }


        public function has_xrp_data() {
            if ( empty( $this->xrp_wallet ) ) {
                return false;
            }

            if ( empty( $this->xrpl_webhook_api_pub ) ) {
                return false;
            }

            if ( empty( $this->xrpl_webhook_api_priv ) ) {
                return false;
            }

            return true;
        }


        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            /* specity where to obtain our rates from. */
            /* todo: make this a select and be able to pick from different sources. */
            if ( $order->get_currency() === 'EUR' ) {
                $rates = 'https://www.bitstamp.net/api/v2/ticker/xrpeur/';
            } elseif ( $order->get_currency() === 'USD' ) {
                $rates = 'https://www.bitstamp.net/api/v2/ticker/xrpusd/';
            } else {
                return false;
            }

            /* use curl, if available. */
            if ( ! ($ch = curl_init( $rates )) ) {
                return false;
            }

            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_HEADER, false );
            curl_setopt( $ch, CURLOPT_HTTPGET, true );

            $body = curl_exec($ch);
            $info = curl_getinfo($ch);

            curl_close($ch);

            /* todo: perhaps add some caching here in case bitstamp is offline. */
            if ( ($rate = json_decode( $body )) === null ) {
                return false;
            }

            /* calculate the amount in XRP. */
            $xrp = round( ceil( ( $order->get_total() / $rate->last ) * 1000000 ) / 1000000, 6);

            /* try to get the destination tag as random as possible. */
            if ( function_exists( 'random_int' ) ) {
                $tag = random_int( 1, 4294967295 );
            } else {
                $tag = mt_rand( 1, 4294967295 );
            }

            $order->add_meta_data( 'total_amount', $xrp );
            $order->add_meta_data( 'destination_tag', $tag );
            $order->add_meta_data( 'delivered_amount', '0' );
            $order->save_meta_data();

            WC()->cart->empty_cart();

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order ),
            );
        }


        /**
         * Parse the most recent transactions and match them against our orders.
         */
        public function check_ledger() {
            $node = $this->get_option( 'xrp_node' );
            if ( empty( $node ) ) {
                $node = 'https://s2.ripple.com:51234';
            }
            if ( ! ($ch = curl_init( $node )) ) {
                return false;
            }

            $account = $this->get_option( 'xrp_account' );
            if ( empty( $account ) ) {
                return false;
            }

            $limit = (int)$this->get_option( 'tx_limit' );
            if ( $limit === 0 ) {
                $limit = 10;
            }

            $payload = [
                'method' => 'account_tx',
                'params' => [[
                    'account' => $account,
                    'ledger_index_min' => -1,
                    'ledger_index_max' => -1,
                    'limit' => $limit,
                ]]
            ];

            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_HEADER, false );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($payload) );

            $data = curl_exec( $ch );
            $info = curl_getinfo( $ch );

            curl_close( $ch );

            if ($info['http_code'] !== 200 || ($res = json_decode( $data )) == null ) {
                return false;
            }

            $rev = array_reverse($res->result->transactions);

            foreach ($rev as $tx) {
                if ( $tx->tx->TransactionType != 'Payment' || $tx->tx->Destination != $account || !isset($tx->tx->DestinationTag) ) {
                    continue;
                }
                $orders = wc_get_orders( array( 'destination_tag' => $tx->tx->DestinationTag ) );
                if ( empty( $orders ) ) {
                    continue;
                }
                $seq = $orders[0]->get_meta('last_sequence');
                if ( $seq != '' && $tx->tx->Sequence <= (int)$seq ) {
                    continue;
                }
                $orders[0]->update_meta_data( 'last_sequence', $tx->tx->Sequence );

                $delivered_amount = $orders[0]->get_meta( 'delivered_amount' );
                $delivered_amount += $tx->tx->Amount / 1000000;
                $orders[0]->update_meta_data( 'delivered_amount', $delivered_amount );
                $orders[0]->save_meta_data();

                $total_amount = $orders[0]->get_meta('total_amount');

                if ($delivered_amount >= $total_amount) {
                    $orders[0]->update_status( 'processing', __( sprintf( '%s XRP received', $delivered_amount ), 'wc-gateway-xrp' ) );
                    $orders[0]->reduce_order_stock();
                }
            }

            exit;
        }
    }
}


/**
 * Add the XRP payment gateway.
 */
function wc_xrp_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_Gateway_XRP';
    return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_xrp_add_to_gateways' );


/**
 * Add custom meta_query so we can search by destination_tag.
 */
function handle_destination_tag_query( $query, $query_vars ) {
    if ( ! empty( $query_vars['destination_tag'] ) ) {
        $query['meta_query'][] = array(
            'key' => 'destination_tag',
            'value' => esc_attr( $query_vars['destination_tag'] ),
        );
    }

    return $query;
}
add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'handle_destination_tag_query', 10, 2 );


/*
 * Customize the "thank you" page in order to display payment info.
 */
function thankyou_xrp_payment_info( $order_id ){
    $gateway = new WC_Gateway_XRP;
    $account = $gateway->get_xrp_account();
 ?>
    <h2>XRP payment details</h2>
    <div class="xrp_qr">
        <img src="https://chart.googleapis.com/chart?chs=256x256&cht=qr&chld=M|0&chl=https%3A%2F%2Fripple.com%2Fsend%3Fto%3D<?php echo urlencode($account) ?>%26dt%3D<?php echo get_post_meta( $order_id, 'destination_tag', true ) ?>&choe=UTF-8">
    </div>
    <table class="woocommerce-table shop_table xrp_info">
        <tbody>
            <tr>
                <th>XRP Account:</th>
                <td><?php echo $account ?></td>
            </tr>
            <tr>
                <th>Destination tag</th>
                <td colspan="2"><?php echo get_post_meta( $order_id, 'destination_tag', true ) ?></td>
            </tr>
            <tr>
                <th>XRP total</th>
                <td colspan="2"><?php echo round( get_post_meta( $order_id, 'total_amount', true ), 6 ) ?></td>
            </tr>
            <tr>
                <th>XRP received</th>
                <td colspan="2"><?php echo round( get_post_meta( $order_id, 'delivered_amount', true ), 6 ) ?></td>
            </tr>
            <tr>
                <th>XRP left to pay</th>
                <td colspan="2"><?php echo round( (float)get_post_meta( $order_id, 'total_amount', true ) - (float)get_post_meta( $order_id, 'delivered_amount', true ) , 0 ) ?></td>
            </tr>
            <tr>
                <th>Order status</th>
                <td colspan="2">
                <?php
                switch (get_post_status( $order_id )) {
                    case 'wc-pending':
                        echo 'Pending payment';
                        break;
                    case 'wc-processing':
                        echo 'Processing (Paid)';
                        break;
                    case 'wc-on-hold':
                        echo 'On hold';
                        break;
                    case 'wc-completed':
                        echo 'Completed';
                        break;
                    case 'wc-cancelled':
                        echo 'Cancelled';
                        break;
                    case 'wc-refunded':
                        echo 'Refunded';
                        break;
                    case 'wc-failed':
                        echo 'Failed';
                        break;
                }
                ?>
                </td>
            </tr>
        </tbody>
    </table>
    <?php if (get_post_status( $order_id ) == 'wc-pending') { ?>
    <script type="text/javascript">
        window.setTimeout(function(){ document.location.reload(true); }, 10000);
    </script>
    <?php
    }
}
add_action( 'woocommerce_thankyou', 'thankyou_xrp_payment_info', 10 );
add_action( 'woocommerce_view_order', 'thankyou_xrp_payment_info', 10 );
