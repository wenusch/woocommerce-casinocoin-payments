# WooCommerce XRP

A payment gateway for [WooCommerce](https://woocommerce.com/) to easily accept [XRP](https://ripple.com/xrp) as a payment method.

## Requirements

* [PHP](https://php.net) 7.0+ (it *might* work with PHP 5.6, but not tested)
* [WordPress](https://wordpress.org/) 5.1+
* [WooCommerce](https://woocommerce.com/) 3.5.6+
* A "self-owned" and **activated** [XRP](https://ripple.com/xrp) account. (You may **not** use an Exchange!)
* You need an account at [XRPL Webhook](https://webhook.xrpayments.co) (see below)

## Installing

* Grab the [latest release](https://github.com/empatogen/woocommerce-xrp/archive/v1.0.3.zip) of the plugin and unzip it in your /wp-content/plugins directory.
* Go to your _Settings > Plugins_ page and **activate** the plugin.
* Create a free account at [XRPL Webhook](https://webhook.xrpayments.co) and obtain your **API keys**. This is required as the plugin uses this webhook to update the checkout page whenever a payment is made.
* Go to _WooCommerce > Settings > Payments_ and configure the XRP payment gateway.

## Changelog

A brief description of what each release brings. If you need more details, check the commit log.

* **v1.0.3** - Adds 3 new exchanges ([Bitbank](https://bitbank.cc/), [Bitcoin Exchange Thailand](https://bx.in.th/) and [Bitlish](https://bitlish.com/)), 31 new currencies, new translations (Dutch and Japanese) and a lot of bugfixes and code clean up!
* **v1.0.2** - Security update to add mitigations for [the partial payments exploit](https://developers.ripple.com/partial-payments.html#partial-payments-exploit).
* **v1.0.1** - Removed the cURL dependency and use wp_remote_get() and wp_remote_post() instead.
* **v1.0.0** - Initial release!

Please keep an extra eye on this repo or [follow me on Twitter](https://twitter.com/empatogen) to get notified about updates and issues.

## License

Please see [LICENSE](https://github.com/empatogen/woocommerce-xrp/blob/master/LICENSE).

## Acknowledgments

* A huge thank you to both [Ripple](https://ripple.com/) and [XRPL Labs](https://xrpl-labs.com/) for being awesome!
* Thanks to the translators! ([Chronos Ananké](https://twitter.com/AnankeChronos), [Jens Twesmann](https://twitter.com/jtwesmann), [Bert de Hoogh](https://twitter.com/BertdeHoogh1) and [Tarotaro](https://twitter.com/tarotaro080808))
* Thanks to **everyone** who tests new unreleased code to help us nail bugs!

## Donate

If you like this plugin and wish to donate, feel free to send some [XRP](https://ripple.com/xrp) to **rscKqdNj1ECXamMoxxwejTnBmhwTpBvTKz**.
