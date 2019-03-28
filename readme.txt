=== WooCommerce XRP ===
Contributors: empatogen
Donate link: rscKqdNj1ECXamMoxxwejTnBmhwTpBvTKz
Tags: woo, woocommerce, xrp, gateway, cryptocurrency
Requires at least: 5.1
Tested up to: 5.1.1
Requires PHP: 7.0
Stable tag: trunk
License: ISC License

WooCommerce XRP is a payment gateway for WooCommerce that easily lets you accept [XRP](https://ripple.com/xrp) as payments method.

== Description ==

WooCommerce XRP is a payment gateway which makes it easy to accept [XRP](https://ripple.com/xrp) as a payment method. You can choose between multiple different cryptocurrency exchanges in order to get the latest XRP rates upon checkout. With the ease of [XRPL Webhooks](https://webhook.xrpayments.co), the order is updated in seconds after the payment is made. This is particularly useful if your customer is making a partial payment, since a new QR-code is generated with the remaining amount of XRP to pay to complete the order.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/woocommerce-xrp` directory folder, or install the plugin through the WordPress plugin screen directly.
1. Activate the plugin through the `Plugins` screen in Wordpress.
1. Create a free account at [XRPL Webhook](https://webhook.xrpayments.co) and obtain your **API keys**. This is required as the plugin uses this webhook to update the checkout page whenever a payment is made.
1. Go to "WooCommerce -> Settings -> Payments" and configure the plugin.

== FAQ ==

= Which XRP server (rippled) is used by default? =

The node **s2.ripple.com** is being used to talk to the XRP network. This can easily be changed under *Advanced* and you can use any public XRP server.

= What is XRPL Webhooks? =

XRPL Webhooks is a free service that send HTTP requests to a specified URL each time a transaction is made to a specific XRP account. The plugin use this service to update the order in real-time when the customer makes a payment.

= Which exchanges is supported? =

You can specify between [Binance](https://www.binance.com/), [Bitbank](https://bitbank.cc/), [Bitfinex](https://www.bitfinex.com/), [Bitlish](https://bitlish.com/), [BitMEX](https://www.bitmex.com/), [Bitrue](https://www.bitrue.com/), [Bitsane](https://bitsane.com/), [Bitstamp](https://www.bitstamp.net/), [Bittrex](https://www.bittrex.com), [Bitcoin Exchange Thailand](https://bx.in.th/), [CEX.IO](https://cex.io/), [Coinbase](https://www.coinbase.com/), [Kraken](https://www.kraken.com/) or [Uphold](https://uphold.com/) as the exchange to use when fetching the XRP rate when the customer is checking out.

= Which base currencies are supported? =

The supported currencies are AUD, BGN, BRL, CAD, CHF, CNY, CZK, DKK, EUR, GBP, HKD, HRK, HUF, IDR, ILS, INR, ISK, JPY, KRW, MXN, MYR, NOK, NZD, PHP, PLN, RON, RUB, SEK, SGD, THB, TRY, USD and ZAR. The rates are updated daily using the [XML feed](https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml) from [ECB](https://www.ecb.europa.eu).

= What does the bypass firewall feature do? =

By default, we speak [JSON-RPC](https://en.wikipedia.org/wiki/JSON#JSON-RPC) on port 51234 with the XRP server. Some webservers are behind a firewall that doesn't allow outgoing traffic on non-standard ports. By enabling this feature, we talk to [cors-anywhere.herokuapp.com](https://cors-anywhere.herokuapp.com/), using TLS on port 443, which then acts as a proxy and relays the traffic to the XRP server.

== Changelog ==

= 1.1.0 =
* Remove the use of Google Chart to generate the QR-code. Use [qrcodejs](https://github.com/davidshimjs/qrcodejs) instead.
* Change to a singleton layout to avoid conflicting with other plugins.
* Add check to ensure that the specified XRP account is activated.
* Add a new exchange. ([Bitrue](https://www.bitrue.com/))
* Fix a bug which prevents us from running on 32bit systems.

= 1.0.3 =
* Add 3 new exchanges. ([Bitbank](https://bitbank.cc/), [Bitcoin Exchange Thailand](https://bx.in.th/) and [Bitlish](https://bitlish.com/))
* Add 31 new currencies.
* Add 2 new languages. (Dutch and Japanese))
* Add a proxy feature to bypass your servers firewall.
* Lots of bugfixes.

= 1.0.2 =
* Add mitigations against [the partial payment exploit](https://developers.ripple.com/partial-payments.html#partial-payments-exploit).

= 1.0.1 =
* Remove the cURL dependency and use wp_remote_get() and wp_remote_post() instead.

= 1.0.0 =
* Initial release!

== Acknowledgments ==

* A huge thank you to both [Ripple](https://ripple.com/) and [XRPL Labs](https://xrpl-labs.com/) for being awesome.
* A huge thank you to [Jens Twesmann](https://twitter.com/jtwesmann) for his code contributions.
* Thanks to the translators! ([Chronos Ananké](https://twitter.com/AnankeChronos), [Bert de Hoogh](https://twitter.com/BertdeHoogh1) and [Tarotaro](https://twitter.com/tarotaro080808))
* Thanks to **everyone** who tests new unreleased code to help us nail bugs.
