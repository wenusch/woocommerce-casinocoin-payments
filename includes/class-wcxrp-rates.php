<?php

class WCXRP_Rates
{
    private $ecb_cache = 'woo_ecb_rates.xml';

    /**
     * Rates constructor.
     * @param $base_currency
     */
    public function __construct($base_currency)
    {
        $this->base_currency = strtoupper($base_currency);
    }

    /**
     * Get rated from the European Central Bank
     * @return bool|string
     */
    private function get_ecb_rates()
    {
        if (($data = $this->get_ecb_cache()) === false) {
            $res = wp_remote_get('https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');
            if (is_wp_error($res) || $res['response']['code'] !== 200) {
                return false;
            }
            if (($data = $this->set_ecb_cache($res['body'])) === false ) {
                return false;
            }
        }

        return $data;
    }

    /**
     * Caching of ECB rates
     * @param $data
     * @return bool
     */
    private function set_ecb_cache($data)
    {
        $cache = get_temp_dir() . $this->ecb_cache;
        if (($fh = fopen($cache, 'w+') ) === false) {
            return false;
        }
        if (fwrite($fh, $data) === false) {
            return false;
        }
        fclose($fh);
        touch($cache);

        return $data;
    }

    /**
     * Get cached data
     * @return bool|string
     */
    private function get_ecb_cache()
    {
        $cache = get_temp_dir() . $this->ecb_cache;
        if (!file_exists($cache) || !is_readable($cache)) {
            return false;
        }
        if (date('N') > 5) {
            return false;
        }
        if (filemtime($this->cache) < strtotime('16:00:00 CET')) {
            return false;
        }

        if (($fh = fopen($cache, 'r')) === false) {
            return false;
        }
        $data = fread($fh, filesize($cache));
        fclose($fh);

        return $data;
    }


    /**
     * @param $currency
     * @return bool
     */
    public function eur($currency)
    {
        if (($data = $this->get_ecb_rates()) === false) {
            return false;
        }
        $regex = sprintf(
            "/\<Cube currency='%s' rate='([^']+)'\/\>/",
            preg_quote($currency)
        );
        if (!preg_match($regex, $data, $match)) {
            return false;
        }

        return $match[1];
    }

    /**
     * @param $rate
     * @param $src
     * @return bool|float|int
     */
    private function to_base($rate, $src)
    {
        if (empty($rate) || empty($src)) {
            return false;
        }

        if ($src === $this->base_currency) {
            return (float)$rate;
        }

        if (($eur = $this->eur($this->base_currency)) === false) {
            return false;
        }

        if ($src == 'EUR') {
            return (float)($rate * $eur);
        }

        if ($src != 'USD' || (($usd = $this->eur('USD')) === false)) {
            return false;
        }

        return ($rate / $usd) * $eur;
    }

    /**
     * Get XRP exchange rate
     * @param $exchange
     * @param array $exchanges
     * @return bool|float|int
     */
    public function get_rate($exchange, array $exchanges)
    {
        /* call the rate dynamically if it's in the exchanges list */
        if (isset($exchanges[$exchange]) &&
        method_exists(get_class($this), $exchange)) {
            $rate = $this->$exchange();
        } else {
            $rate = $this->bitstamp();
        }

        /* return the rate, if we got one */
        if ($rate !== false) {
            return $rate;
        }

        /* fallback exchange */
        if ($exchange !== 'bitstamp' && ($rate = $this->bitstamp()) !== false) {
            return $rate;
        } elseif ($exchange === 'bitstamp' && ($rate = $this->kraken()) !== false ) {
            return $rate;
        }

        return false;
    }

    /**
     * Get Exchange rate from bitstamp
     * @return bool|float|int
     */
    private function bitstamp()
    {
        if ($this->base_currency === 'USD') {
            $url = 'https://www.bitstamp.net/api/v2/ticker/xrpusd/';
            $src = 'USD';
        } else {
            $url = 'https://www.bitstamp.net/api/v2/ticker/xrpeur/';
            $src = 'EUR';
        }
        $res = wp_remote_get($url);
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        return $this->to_base($rate->last, $src);
    }

    /**
     * Get Exchange rate from kraken
     * @return bool|float|int
     */
    private function kraken()
    {
        if ($this->base_currency === 'USD') {
            $url = 'https://api.kraken.com/0/public/Ticker?pair=XRPUSD';
            $src = 'USD';
        } else {
            $url = 'https://api.kraken.com/0/public/Ticker?pair=XRPEUR';
            $src = 'EUR';
        }
        $res = wp_remote_get($url);
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        /* ugly? */
        foreach ($rate->result as $rate) {
            $rate = (float)$rate->c[0];
            break;
        };

        return $this->to_base($rate, $src);
    }

    /**
     * Get Exchange rate from bitfinex
     * @return bool|float|int
     */
    private function bitfinex()
    {
        $res = wp_remote_get('https://api.bitfinex.com/v1/pubticker/xrpusd');
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        return $this->to_base($rate->last_price, 'USD');
    }

    /**
     * Get Exchange rate from bittrex
     * @return bool|float|int
     */
    private function bittrex()
    {
        $res = wp_remote_get('https://api.bittrex.com/api/v1.1/public/getticker?market=USD-XRP');
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        return $this->to_base($rate->result->Last, 'USD');
    }

    /**
     * Get Exchange rate from bitmex
     * @return bool|float|int
     */
    private function bitmex()
    {
        $res = wp_remote_get('https://www.bitmex.com/api/v1/orderBook/L2?symbol=xbt&depth=1');
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }
        $btc = $rate[0]->price;

        $res = wp_remote_get('https://www.bitmex.com/api/v1/orderBook/L2?symbol=xrp&depth=1');
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        return $this->to_base(($btc * $rate[0]->price), 'USD');
    }

    /**
     * Get Exchange rate from binance
     * @return bool|float|int
     */
    private function binance()
    {
        $res = wp_remote_get('https://api.binance.com/api/v3/ticker/price?symbol=XRPUSDT');
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        return $this->to_base($rate->price, 'USD');
    }

    /**
     * Get Exchange rate from bx.in
     * @return bool|float|int
     */
    private function bxinth()
    {
        $res = wp_remote_get('https://bx.in.th/api/');
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        /* ugly? */
        foreach ($rate as $r) {
            if ($r->primary_currency === 'THB' &&
            $r->secondary_currency === 'XRP') {
                $rate = $r->last_price;
                break;
            }
        }

        return $this->to_base($rate, 'THB');
    }

    /**
     * Get Exchange rate from bitlish
     * @return bool|float|int
     */
    private function bitlish()
    {
        $res = wp_remote_get('https://bitlish.com/api/v1/tickers');
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        if ($this->base_currency === 'GBP') {
            $rate = $rate->xrpgbp->last;
            $src = 'GBP';
        } else {
            $rate = $rate->xrpeur->last;
            $src = 'EUR';
        }

        return $this->to_base($rate, $src);
    }

    /**
     * Get Exchange rate from bitbank
     * @return bool|float|int
     */
    private function bitbank()
    {
        $res = wp_remote_get('https://public.bitbank.cc/xrp_jpy/ticker');
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        return $this->to_base($rate->data->last, 'JPY');
    }

    /**
     * Get Exchange rate from bitrue
     * @return bool|float|int
     */
    private function bitrue()
    {
        $res = wp_remote_get('https://www.bitrue.com/kline-api/publicXRP.json?command=returnTicker');
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        return $this->to_base($rate->data->XRP_USDT->last, 'USD');
    }

    /**
     * Get Exchange rate from cexio
     * @return bool|float|int
     */
    private function cexio()
    {
        if ($this->base_currency === 'USD') {
            $url = 'https://cex.io/api/ticker/XRP/USD';
            $src = 'USD';
        } else {
            $url = 'https://cex.io/api/ticker/XRP/EUR';
            $src = 'EUR';
        }
        $res = wp_remote_get($url);
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        return $this->to_base($rate->last, $src);
    }

    /**
     * Get Exchange rate from uphold
     * @return bool|float|int
     */
    private function uphold()
    {
        if ($this->base_currency === 'USD') {
            $url = 'https://api.uphold.com/v0/ticker/XRPUSD';
            $src = 'USD';
        } else {
            $url = 'https://api.uphold.com/v0/ticker/XRPEUR';
            $src = 'EUR';
        }
        $res = wp_remote_get($url);
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        return $this->to_base($rate->ask, $src);
    }

    /**
     * Get Exchange rate from coinbase
     * @return bool|float|int
     */
    private function coinbase()
    {
        if ($this->base_currency === 'USD') {
            $url = 'https://api.coinbase.com/v2/prices/XRP-USD/buy';
            $src = 'USD';
        } else {
            $url = 'https://api.coinbase.com/v2/prices/XRP-EUR/buy';
            $src = 'EUR';
        }
        $res = wp_remote_get($url);
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        return $this->to_base($rate->amount, $src);
    }

    /**
     * Get Exchange rate from bitsane
     * @return bool|float|int
     */
    private function bitsane()
    {
        if ($this->base_currency === 'USD') {
            $url = 'https://bitsane.com/api/public/ticker?pairs=XRP_USD';
            $src = 'USD';
        } else {
            $url = 'https://bitsane.com/api/public/ticker?pairs=XRP_EUR';
            $src = 'EUR';
        }
        $res = wp_remote_get($url);
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        if ($src === 'USD') {
            return $this->to_base($rate->XRP_USD->last, $src);
        } else {
            return $this->to_base($rate->XRP_EUR->last, $src);
        }
    }
}
