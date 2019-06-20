<?php
/**
 * CSC Payment Gateway
 *
 * Provides an Payment Gateway to accept payments using CSC.
 *
 * @class       WC_Gateway_CSC
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce/Classes/Payment
 * @author      Massimo Wenusch
 */
class WC_Gateway_CSC extends \WC_Payment_Gateway
{
    public $helpers;
    protected $exchanges;
    protected $currencies;

    public function __construct()
    {
        $this->id                 = 'csc';
        $this->has_fields         = false;
        $this->method_title       = __('CasinoCoin', 'wc-gateway-csc');
        $this->method_description = __('Let your customers pay using the CSC Ledger.', 'wc-gateway-csc');

        $this->init_settings();

        $this->helpers = new WCCSC_Helpers();

        /* supported exchanges */
        $this->exchanges = [
            'bitrue'  => 'bitrue.com',

        ];

        /* supported currencies */
        $this->currencies = [
            'USD','JPY','BGN','CZK','DKK','GBP','HUF','PLN','RON','SEK',
            'CHF','ISK','NOK','HRK','RUB','TRY','AUD','BRL','CAD','CNY',
            'HKD','IDR','ILS','INR','KRW','MXN','MYR','NZD','PHP','SGD',
            'THB','ZAR','EUR','CSC'
        ];

        /* sort the exchanges alphabetically */
        natcasesort($this->exchanges);

        $this->title                 = $this->settings['title'];
        $this->description           = $this->settings['description'];
        $this->csc_account           = $this->settings['csc_account'];
        $this->cscl_webhook_api_pub  = $this->settings['cscl_webhook_api_pub'];
        $this->cscl_webhook_api_priv = $this->settings['cscl_webhook_api_priv'];
        $this->csc_node              = $this->settings['csc_node'];
        $this->tx_limit              = $this->settings['tx_limit'];

        add_action('woocommerce_api_wc_gateway_csc', [$this, 'check_ledger']);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        if (!is_admin()) {
            return true;
        }

        if (empty($this->settings['csc_account']) ||
        empty($this->settings['cscl_webhook_api_pub']) ||
        empty($this->settings['cscl_webhook_api_priv'])) {
            add_action('admin_notices', [$this, 'require_csc']);
        } elseif ($this->check_webhooks() === false) {
            add_action('admin_notices', [$this, 'invalid_csc']);
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
        _e('<div class="notice notice-error"><p><b>WooCommerce CasinonCion Payments</b> does not support the <b>currency</b> your shop is using.</p></div>', 'wc-gateway-csc');
    }

    /**
    * Display an error that all CSC related data is required.
    */
    public function require_csc()
    {
        _e('<div class="notice notice-error"><p><b>WooCommerce CasinonCion Payments</b> requires you to specify a <b>CSC Account</b> and your <b>CSCL Webhook</b> details.</p></div>', 'wc-gateway-csc');
    }

    /**
    * Display an error that the CSC details is invalid.
    */
    public function invalid_csc()
    {
        _e('<div class="notice notice-error"><p>The specified <b>CSC Account</b> and/or <b>CSCL Webhook</b> details are invalid. Please correct these for <b>WooCommerce CasinonCion Payments</b> to work properly.</p></div>', 'wc-gateway-csc');
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
        if (empty($this->settings['cscl_webhook_api_pub']) ||
        empty($this->settings['cscl_webhook_api_priv'])) {
            return false;
        }

        $wh = new WCCSC_Webhook(
            $this->settings['cscl_webhook_api_pub'],
            $this->settings['cscl_webhook_api_priv']
        );

        /* webhooks */
        if (($hooks = $wh->webhooks()) === false) {
            return false;
        }
        $url = WC()->api_request_url('WC_Gateway_CSC');
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
            if ($sub->address === $this->settings['csc_account']) {
                $exists = true;
                break;
            }
        }
        if ($exists === false &&
        $wh->add_subscription($this->settings['csc_account']) === false) {
            return false;
        }

        /* make sure the csc is activated */
        $ledger = new WCCSC_Ledger(
            $this->settings['csc_node'],
            $this->settings['csc_bypass']
        );
        $trans = $ledger->account_info($this->settings['csc_account']);

        if ($trans->status === 'error') {
            return false;
        }

        return true;
    }

    /**
    * Return our CSC account.
    */
    public function get_csc_account()
    {
        return $this->csc_account;
    }

    /**
    * Initialize Gateway Settings Form Fields
    */
    public function init_form_fields() {

        $this->form_fields = apply_filters('wc_csc_form_fields', [
            'enabled' => [
                'title'   => __('Enable/Disable', 'wc-gateway-csc'),
                'type'    => 'checkbox',
                'label'   => __('Enable CSC Payments', 'wc-gateway-csc'),
                'default' => 'no'
            ],
            'title' => [
                'title'       => __('Title', 'wc-gateway-csc'),
                'type'        => 'text',
                'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-csc'),
                'default'     => __('CasinoCoin', 'wc-gateway-csc'),
                'desc_tip'    => true
            ],
            'description' => [
                'title'       => __('Description', 'wc-gateway-csc'),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'wc-gateway-csc'),
                'default'     => __('Payment instruction will be shown once you\'ve placed your order.', 'wc-gateway-csc'),
                'desc_tip'    => true
            ],
            'csc' => [
                'title'       => __('CSC Account', 'wc-gateway-csc'),
                'type'        => 'title',
                'description' => __('Please specify the CSC Ledger account where your payments should be sent. This should be an account <b>YOU</b> own and should <b>NOT</b> be an exchange account, since a unique destination tag is generated for each order.', 'wc-gateway-csc')
            ],
            'csc_account' => [
                'title'       => __('CSC Account', 'wc-gateway-csc'),
                'type'        => 'text',
                'description' => __('Your CSC account where payments should be sent.', 'wc-gateway-csc'),
                'default'     => '',
                'desc_tip'    => true
            ],
            'cscl_webhook' => [
                'title'       => __('CSCL Webhook options', 'wc-gateway-csc'),
                'type'        => 'title',
                'description' => __('In order to create your webhook and process your payments properly, please specify your CSCL Webhooks API key. For more informations how to obtain these keys, please visit <a href="https://webhook.casinocoin.services">https://webhook.casinocoin.services</a>.', 'wc-gateway-csc')
            ],
            'cscl_webhook_api_pub' => [
                'title'       => __('API Key', 'wc-gateway-csc'),
                'type'        => 'text',
                'description' => __('Your CSCL CSCayments Webhook API key.', 'wc-gateway-csc'),
                'default'     => '',
                'desc_tip'    => true
            ],
            'cscl_webhook_api_priv' => [
                'title'       => __('API Secret', 'wc-gateway-csc'),
                'type'        => 'text',
                'description' => __('Your CSCL CSCayments Webhook API secret.', 'wc-gateway-csc'),
                'default'     => '',
                'desc_tip'    => true
            ],
            'advanced' => [
                'title'       => __('Advanced', 'wc-gateway-csc'),
                'type'        => 'title',
                'description' => __('Leave these untouched unless you really know what you\'re doing.', 'wc-gateway-csc')
            ],
            'csc_node' => [
                'title'       => __('CSC Node', 'wc-gateway-csc'),
                'type'        => 'text',
                'description' => __('Which CSC node to use when checking our balance.', 'wc-gateway-csc'),
                'default'     => 'https://csc-node-de-a.casinocoin.eu:5005',
                'placeholder' => 'https://csc-node-de-a.casinocoin.eu:5005',
                'desc_tip'    => true
            ],
            'csc_bypass' => [
                'title'       => __('Bypass firewall', 'wc-gateway-csc'),
                'type'        => 'checkbox',
                'label'       => __('Use a proxy to bypass your webservers firewall.', 'wc-gateway-csc'),
                'description' => 'This is useful if your webserver does not allow outbound traffic on non-standard ports.',
                'default'     => 'no',
                'desc_tip'    => true
            ],
            'exchange' => [
                'title'       => __('Exchange', 'wc-gateway-csc'),
                'type'        => 'select',
                'description' => __('Which exchange to use when fetching the CSC rate.', 'wc-gateway-csc'),
                'options'     => $this->exchanges,
                'default'     => 'bitrue',
                'desc_tip'    => true
            ],
            'tx_limit' => [
                'title'       => __('Transaction Limit', 'wc-gateway-csc'),
                'type'        => 'number',
                'description' => __('The number of transactions to fetch from the ledger each time we check for new payments.', 'wc-gateway-csc'),
                'default'     => 10,
                'desc_tip'    => true
            ]
        ]);
    }

    /**
    * Process the order and calculate the price in CSC.
    */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $ledger = new WCCSC_Ledger(
            $this->settings['csc_node'],
            $this->settings['csc_bypass']
        );

        /* specify where to obtain our rates from. */
        $rates = new WCCSC_Rates(
            $ledger,
            $this->settings['csc_account'],
            $order->get_currency()
        );
        $rate = $rates->get_rate(
            $this->settings['exchange'],
            $this->exchanges
        );

        if ($rate === false) {
            return false;
        }

        /* round to our advantage with 8 decimals */
        $csc = round(ceil(($order->get_total() / $rate) * 100000000) / 100000000, 8);

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

        $order->add_meta_data('total_amount', $csc);
        $order->add_meta_data('destination_tag', $tag);
        $order->add_meta_data('delivered_amount', '0');
        $order->add_meta_data('csc_rate', $rate);
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
        $ledger = new WCCSC_Ledger(
            $this->settings['csc_node'],
            $this->settings['csc_bypass']
        );
        $trans = $ledger->account_tx(
            $this->settings['csc_account'],
            (int)$this->settings['tx_limit']
        );
        if ($trans === false) {
            header('HTTP/1.0 500 Internal Server Error', true, 500);
            echo "unable to reach the CSC ledger.";
            exit;
        }

        $dropsToCsc = 100000000;

        foreach ($trans as $tx) {
            /* only care for payment transactions */
            if ($tx->tx->TransactionType !== 'Payment') {
                continue;
            }

            /* only care for inbound transactions */
            if ($tx->tx->Destination !== $this->settings['csc_account']) {
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
            $delivered_csc    = (float)$orders[0]->get_meta('delivered_amount');
            $delivered_drops  = $delivered_csc * $dropsToCsc;

            /* update current payment */
            $delivered_drops += $tx->meta->delivered_amount;
            $delivered_csc    = $delivered_drops / $dropsToCsc;

            /* update delivered_amount */
            $orders[0]->update_meta_data('delivered_amount', $delivered_csc);

            /* check if the delivered amount is enough */
            $total_drops = (float)$orders[0]->get_meta('total_amount') * $dropsToCsc;

            if (abs($delivered_drops) == abs($total_drops)) {
                $orders[0]->update_status(
                    'processing',
                    __(sprintf('%s CSC received', $delivered_csc), 'wc-gateway-csc')
                );
                $orders[0]->reduce_order_stock();
            } elseif (abs($delivered_drops) > abs($total_drops)) {
                $orders[0]->update_meta_data(
                    'overpaid_amount',
                    (abs($delivered_drops) - abs($total_drops)) / $dropsToCsc
                );
                $orders[0]->update_status(
                    'overpaid',
                    __(sprintf('%s CSC received', $delivered_csc), 'wc-gateway-csc')
                );
                $orders[0]->reduce_order_stock();
            }

            $orders[0]->save_meta_data();
        }

        echo "ok";
        exit;
    }
}
