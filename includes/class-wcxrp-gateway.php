<?php
/**
 * XRP Payment Gateway
 *
 * Provides an Payment Gateway to accept payments using XRP.
 *
 * @class       WC_Gateway_XRP
 * @extends     WC_Payment_Gateway
 * @version     1.1.0
 * @package     WooCommerce/Classes/Payment
 * @author      Jesper Wallin
 */
class WC_Gateway_XRP extends \WC_Payment_Gateway
{
    public $helpers;
    protected $exchanges;
    protected $currencies;

    public function __construct()
    {
        $this->id                 = 'xrp';
        $this->has_fields         = false;
        $this->method_title       = __('XRP', 'wc-gateway-xrp');
        $this->method_description = __('Let your customers pay using the XRP Ledger.', 'wc-gateway-xrp');

        $this->init_settings();

        $this->helpers = new WCXRP_Helpers();

        /* supported exchanges */
        $this->exchanges = [
            'binance'  => 'Binance',
            'bitbank'  => 'Bitbank',
            'bitfinex' => 'Bitfinex',
            'bitlish'  => 'Bitlish',
            'bitmex'   => 'BitMEX',
            'bitrue'   => 'Bitrue',
            'bitsane'  => 'Bitsane',
            'bitstamp' => 'Bitstamp',
            'bittrex'  => 'Bittrex',
            'bxinth'   => 'Bitcoin Exchange Thailand',
            'cexio'    => 'CEX.IO',
            'coinbase' => 'Coinbase',
            'kraken'   => 'Kraken',
            'uphold'   => 'Uphold'
        ];

        /* supported currencies */
        $this->currencies = [
            'USD','JPY','BGN','CZK','DKK','GBP','HUF','PLN','RON','SEK','CHF',
            'ISK','NOK','HRK','RUB','TRY','AUD','BRL','CAD','CNY','HKD','IDR',
            'ILS','INR','KRW','MXN','MYR','NZD','PHP','SGD','THB','ZAR','EUR'
        ];

        /* sort the exchanges alphabetically */
        natcasesort($this->exchanges);

        $this->title                 = $this->settings['title'];
        $this->description           = $this->settings['description'];
        $this->xrp_account           = $this->settings['xrp_account'];
        $this->xrpl_webhook_api_pub  = $this->settings['xrpl_webhook_api_pub'];
        $this->xrpl_webhook_api_priv = $this->settings['xrpl_webhook_api_priv'];
        $this->xrp_node              = $this->settings['xrp_node'];
        $this->tx_limit              = $this->settings['tx_limit'];

        add_action('woocommerce_api_wc_gateway_xrp', [$this, 'check_ledger']);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        if (!is_admin()) {
            return true;
        }

        if (empty($this->settings['xrp_account']) ||
        empty($this->settings['xrpl_webhook_api_pub']) ||
        empty($this->settings['xrpl_webhook_api_priv'])) {
            add_action('admin_notices', [$this, 'require_xrp']);
        } elseif ($this->check_webhooks() === false) {
            add_action('admin_notices', [$this, 'invalid_xrp']);
        }

        if (!in_array(get_woocommerce_currency(), $this->currencies)) {
            add_action('admin_notices', [$this, 'supported_currencies']);
        }

        $this->init_form_fields();
    }

    /**
    * Display an error that the current currency is unsupported.
    */
    public function supported_currencies()
    {
        _e('<div class="notice notice-error"><p><b>WooCommerce XRP</b> does not support the <b>currency</b> your shop is using.</p></div>', 'wc-gateway-xrp');
    }

    /**
    * Display an error that all XRP related data is required.
    */
    public function require_xrp()
    {
        _e('<div class="notice notice-error"><p><b>WooCommerce XRP</b> requires you to specify a <b>XRP Account</b> and your <b>XRPL Webhook</b> details.</p></div>', 'wc-gateway-xrp');
    }

    /**
    * Display an error that the XRP details is invalid.
    */
    public function invalid_xrp()
    {
        _e('<div class="notice notice-error"><p>The specified <b>XRP Account</b> and/or <b>XRPL Webhook</b> details are invalid. Please correct these for <b>WooCommerce XRP</b> to work properly.</p></div>', 'wc-gateway-xrp');
    }

    /**
    * Save settings and reload.
    */
    public function process_admin_options()
    {
        parent::process_admin_options();

        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    /**
    * Check and make sure the webhook and subscription exists.
    */
    public function check_webhooks()
    {
        if (empty($this->settings['xrpl_webhook_api_pub']) ||
        empty($this->settings['xrpl_webhook_api_priv'])) {
            return false;
        }

        $wh = new WCXRP_Webhook(
            $this->settings['xrpl_webhook_api_pub'],
            $this->settings['xrpl_webhook_api_priv']
        );

        /* webhooks */
        if (($hooks = $wh->webhooks()) === false) {
            return false;
        }
        $url = WC()->api_request_url('WC_Gateway_XRP');
        $exists = false;
        foreach ($hooks as $hook) {
            if ($hook->url === $url) {
                $exists = true;
                break;
            }
        }
        if ($exists === false && $wh->add_webhook($url) === false) {
            return false;
        }

        /* subscriptions */
        if (($subs = $wh->subscriptions()) === false) {
            return false;
        }
        $exists = false;
        foreach ($subs as $sub) {
            if ($sub->address === $this->settings['xrp_account']) {
                $exists = true;
                break;
            }
        }
        if ($exists === false &&
        $wh->add_subscription($this->settings['xrp_account']) === false) {
            return false;
        }

        /* make sure the xrp is activated */
        $ledger = new WCXRP_Ledger(
            $this->settings['xrp_node'],
            $this->settings['xrp_bypass']
        );
        $trans = $ledger->account_info($this->settings['xrp_account']);

        if ($trans->status === 'error') {
            return false;
        }

        return true;
    }

    /**
    * Return our XRP account.
    */
    public function get_xrp_account()
    {
        return $this->xrp_account;
    }

    /**
    * Initialize Gateway Settings Form Fields
    */
    public function init_form_fields() {

        $this->form_fields = apply_filters('wc_xrp_form_fields', [
            'enabled' => [
                'title'   => __('Enable/Disable', 'wc-gateway-xrp'),
                'type'    => 'checkbox',
                'label'   => __('Enable XRP Payments', 'wc-gateway-xrp'),
                'default' => 'no'
            ],
            'title' => [
                'title'       => __('Title', 'wc-gateway-xrp'),
                'type'        => 'text',
                'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-xrp'),
                'default'     => __('XRP', 'wc-gateway-xrp'),
                'desc_tip'    => true
            ],
            'description' => [
                'title'       => __('Description', 'wc-gateway-xrp'),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'wc-gateway-xrp'),
                'default'     => __('Payment instruction will be shown once you\'ve placed your order.', 'wc-gateway-xrp'),
                'desc_tip'    => true
            ],
            'xrp' => [
                'title'       => __('XRP Account', 'wc-gateway-xrp'),
                'type'        => 'title',
                'description' => __('Please specify the XRP Ledger account where your payments should be sent. This should be an account <b>YOU</b> own and should <b>NOT</b> be an exchange account, since a unique destination tag is generated for each order.', 'wc-gateway-xrp')
            ],
            'xrp_account' => [
                'title'       => __('XRP Account', 'wc-gateway-xrp'),
                'type'        => 'text',
                'description' => __('Your XRP account where payments should be sent.', 'wc-gateway-xrp'),
                'default'     => '',
                'desc_tip'    => true
            ],
            'xrpl_webhook' => [
                'title'       => __('XRPL Webhook options', 'wc-gateway-xrp'),
                'type'        => 'title',
                'description' => __('In order to create your webhook and process your payments properly, please specify your XRPL Webhooks API key. For more informations how to obtain these keys, please visit <a href="https://webhook.xrpayments.co/">https://webhook.xrpayments.co</a>.', 'wc-gateway-xrp')
            ],
            'xrpl_webhook_api_pub' => [
                'title'       => __('API Key', 'wc-gateway-xrp'),
                'type'        => 'text',
                'description' => __('Your XRPL XRPayments Webhook API key.', 'wc-gateway-xrp'),
                'default'     => '',
                'desc_tip'    => true
            ],
            'xrpl_webhook_api_priv' => [
                'title'       => __('API Secret', 'wc-gateway-xrp'),
                'type'        => 'text',
                'description' => __('Your XRPL XRPayments Webhook API secret.', 'wc-gateway-xrp'),
                'default'     => '',
                'desc_tip'    => true
            ],
            'advanced' => [
                'title'       => __('Advanced', 'wc-gateway-xrp'),
                'type'        => 'title',
                'description' => __('Leave these untouched unless you really know what you\'re doing.', 'wc-gateway-xrp')
            ],
            'xrp_node' => [
                'title'       => __('XRP Node', 'wc-gateway-xrp'),
                'type'        => 'text',
                'description' => __('Which XRP node to use when checking our balance.', 'wc-gateway-xrp'),
                'default'     => 'https://s2.ripple.com:51234',
                'placeholder' => 'https://s2.ripple.com:51234',
                'desc_tip'    => true
            ],
            'xrp_bypass' => [
                'title'       => __('Bypass firewall', 'wc-gateway-xrp'),
                'type'        => 'checkbox',
                'label'       => __('Use a proxy to bypass your webservers firewall.', 'wc-gateway-xrp'),
                'description' => 'This is useful if your webserver does not allow outbound traffic on non-standard ports.',
                'default'     => 'no',
                'desc_tip'    => true
            ],
            'exchange' => [
                'title'       => __('Exchange', 'wc-gateway-xrp'),
                'type'        => 'select',
                'description' => __('Which exchange to use when fetching the XRP rate.', 'wc-gateway-xrp'),
                'options'     => $this->exchanges,
                'default'     => 'bitstamp',
                'desc_tip'    => true
            ],
            'tx_limit' => [
                'title'       => __('Transaction Limit', 'wc-gateway-xrp'),
                'type'        => 'number',
                'description' => __('The number of transactions to fetch from the ledger each time we check for new payments.', 'wc-gateway-xrp'),
                'default'     => 10,
                'desc_tip'    => true
            ]
        ]);
    }

    /**
    * Process the order and calculate the price in XRP.
    */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        /* specify where to obtain our rates from. */
        $rates = new WCXRP_Rates($order->get_currency());
        $rate  = $rates->get_rate(
            $this->settings['exchange'],
            $this->exchanges
        );

        if ($rate === false) {
            return false;
        }

        /* round to our advantage with 6 decimals */
        $xrp = round(ceil(($order->get_total() / $rate) * 1000000) / 1000000, 6);

        /* check if php is 32bit or 64bit */
        if (PHP_INT_SIZE === 4) {
            $int_max = 2147483646;
        } else {
            $int_max = 4294967295;
        }

        /* try to get the destination tag as random as possible. */
        if (function_exists('random_int')) {
            $tag = random_int(1, $int_max);
        } else {
            $tag = mt_rand(1, $int_max);
        }

        /**
        * make sure the tag hasn't been used already,
        * if so, bail out and have the user try again.
        */
        $orders = wc_get_orders(['destination_tag' => $tag]);
        if (!empty($orders)) {
            return false;
        }

        $order->add_meta_data('total_amount', $xrp);
        $order->add_meta_data('destination_tag', $tag);
        $order->add_meta_data('delivered_amount', '0');
        $order->add_meta_data('xrp_rate', $rate);
        $order->save_meta_data();

        WC()->cart->empty_cart();

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }

    /**
    * Parse the most recent transactions and match them against our orders.
    */
    public function check_ledger()
    {
        $ledger = new WCXRP_Ledger(
            $this->settings['xrp_node'],
            $this->settings['xrp_bypass']
        );
        $trans = $ledger->account_tx(
            $this->settings['xrp_account'],
            (int)$this->settings['tx_limit']
        );
        if ($trans === false) {
            header('HTTP/1.0 500 Internal Server Error', true, 500);
            echo "unable to reach the XRP ledger.";
            exit;
        }

        foreach ($trans as $tx) {
            /* only care for payment transactions */
            if ($tx->tx->TransactionType !== 'Payment') {
                continue;
            }

            /* only care for inbound transactions */
            if ($tx->tx->Destination !== $this->settings['xrp_account']) {
                continue;
            }

            /* only care for transactions with a sane destination tag set */
            if (!isset($tx->tx->DestinationTag) ||
            $tx->tx->DestinationTag === 0) {
                continue;
            }

            /* make sure the delivered_amount meta field is set */
            if (!isset($tx->meta->delivered_amount)) {
                continue;
            }

            $orders = wc_get_orders(['destination_tag' => $tx->tx->DestinationTag]);
            if (empty($orders)) {
                continue;
            }

            /* keep track of the sequence number */
            $seq = $orders[0]->get_meta('last_sequence');
            if ($seq !== '' && $tx->tx->Sequence <= (int)$seq) {
                continue;
            }
            $orders[0]->update_meta_data('last_sequence', $tx->tx->Sequence);

            /* store the tx hash */
            $txlist = $orders[0]->get_meta('tx');
            if (empty($txlist)) {
                $txlist = [];
            } else {
                $txlist = explode(',', $txlist);
            }
            if (!in_array($tx->tx->hash, $txlist)) {
                array_push($txlist, $tx->tx->hash);
            }
            $orders[0]->update_meta_data('tx', implode(',', $txlist));

            /* get previous payments */
            $delivered_xrp    = (float)$orders[0]->get_meta('delivered_amount');
            $delivered_drops  = $delivered_xrp * 1000000;

            /* update current payment */
            $delivered_drops += $tx->meta->delivered_amount;
            $delivered_xrp    = $delivered_drops / 1000000;

            /* update delivered_amount */
            $orders[0]->update_meta_data('delivered_amount', $delivered_xrp);

            /* check if the delivered amount is enough */
            $total_drops = (float)$orders[0]->get_meta('total_amount') * 1000000;

            if (abs($delivered_drops) == abs($total_drops)) {
                $orders[0]->update_status(
                    'processing',
                    __(sprintf('%s XRP received', $delivered_xrp), 'wc-gateway-xrp')
                );
                $orders[0]->reduce_order_stock();
            } elseif (abs($delivered_drops) > abs($total_drops)) {
                $orders[0]->update_meta_data(
                    'overpaid_amount',
                    (abs($delivered_drops) - abs($total_drops)) / 1000000
                );
                $orders[0]->update_status(
                    'overpaid',
                    __(sprintf('%s XRP received', $delivered_xrp), 'wc-gateway-xrp')
                );
                $orders[0]->reduce_order_stock();
            }

            $orders[0]->save_meta_data();
        }

        echo "ok";
        exit;
    }
}
