# CasinoCoin Payments

A payment gateway for [WooCommerce](https://woocommerce.com/) to easily accept [CasinoCoin](https://casinocoin.org) as a payment method. This plugin is rebranded from the original  [XRP](https://github.com/empatogen/woocommerce-xrp) version which is coded by [Jesper Wallin](https://twitter.com/empatogen) and [Jens Twesmann](https://twitter.com/jtwesmann)

## Requirements

* [PHP](https://php.net) 5.6 or greater (PHP 7.2 or higher is recommended)
* [WordPress](https://wordpress.org/) 5.1 or greater
* [WooCommerce](https://woocommerce.com/) 3.5.6 or greater
* A "self-owned" and **activated** [CSC](https://casinocoin.org/) account. 
* You need an account at [CSCL Webhook](https://webhook.casinocoin.services) (see below)

## Installing

1. Upload the plugin to the `/wp-content/plugins/woocommerce-casinocoin-payments` directory folder, or install the plugin through the WordPress plugin screen directly.
1. Activate the plugin through the `Plugins` screen in Wordpress.
1. Create a free account at [CSCL Webhook](https://webhook.casinocoin.services) and obtain your **API keys**. This is required as the plugin uses this webhook to update the checkout page whenever a payment is made.
1. Go to "WooCommerce -> Settings -> Payments" and configure the plugin.

## FAQ ##

### Which CSC server (casinocoind) is used by default?

The plugin connects to **https://csc-node-de-a.casinocoin.eu:5005** for retrieving ledger data. It can be adjusted to another node if the endpoints supports json-rpc calls. 

### What is a CSCL Webhook?

A CSCL Webhook is provided by [CSCL Webhook](https://webhook.casinocoin.services). It sends transaction data to your webshop to be able to verify a payment has been made and update the order status in real-time.

### Which exchange is supported?

Bitrue is supported for retrieving the current CasinoCoin rate. 

### Which base currencies are supported?

The supported currencies are AUD, BGN, BRL, CAD, CHF, CNY, CZK, DKK, EUR, GBP, HKD, HRK, HUF, IDR, ILS, INR, ISK, JPY, KRW, MXN, MYR, NOK, NZD, PHP, PLN, RON, RUB, SEK, SGD, THB, TRY, USD and ZAR. The rates are updated daily using the [XML feed](https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml) from [ECB](https://www.ecb.europa.eu).

### What does the bypass firewall feature do?

By default, [JSON-RPC](https://en.wikipedia.org/wiki/JSON#JSON-RPC) on port 5005 is used to communicate witht the CSC Server. Some webservers are behind a firewall that doesn't allow outgoing traffic on non-standard ports. By enabling this feature, we communicating through [cors-anywhere.herokuapp.com](https://cors-anywhere.herokuapp.com/), using TLS on port 443, which acts as a proxy and relays the traffic to the CSC server.

## Changelog

### 1.0.0
* Initial release of the CasinoCoin Payments plugin.

## License

Please see [LICENSE](https://github.com/wenusch/woocommerce-csc/blob/master/LICENSE).

## Donations

Donations are welcome. Will mostly be used for Sushi. This is my CSC account:

cLjJ4NSXfn4w9nHNNVG8afHccp51FBU3U2

If you would like to donate XRP, please feel free to send it to Jesper Wallin. This is his XRP account:

rscKqdNj1ECXamMoxxwejTnBmhwTpBvTKz
