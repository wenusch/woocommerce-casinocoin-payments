<?php

class WCCSC_Rates
{
    private $ecb_cache = 'woo_ecb_rates.xml';
    private $ledger = false;
    private $account = false;
    private $base_currency = false;

    /**
     * Rates constructor.
     * @param $ledger
     * @param $account
     * @param $base_currency
     */
    public function __construct($ledger, $account, $base_currency)
    {
        $this->ledger = $ledger;
        $this->account = trim($account);
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
     * Get CSC exchange rate
     * @param $exchange
     * @param array $exchanges
     * @return bool|float|int
     */
    public function get_rate($exchange, array $exchanges)
    {
        /* don't bother if we're using CSC as base currency */
        if ($this->base_currency === 'CSC') {
            return 1;
        }

        /* call the rate dynamically if it's in the exchanges list */
        if (isset($exchanges[$exchange]) &&
        method_exists(get_class($this), $exchange)) {
            $rate = $this->$exchange();
        } else {
            $rate = $this->bitrue();
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


    private function getBitcoinPrice()
    {
        $res = wp_remote_get('https://api.coindesk.com/v1/bpi/currentprice.json');
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        return $rate->bpi->USD->rate_float;
    }


    /**
     * Get Exchange rate from bitrue
     * @return bool|float|int
     */
    private function bitrue()
    {
        $res = wp_remote_get('https://www.bitrue.com/kline-api/public.json?command=returnTicker');
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($rate = json_decode($res['body'])) === null) {
            return false;
        }

        return $this->to_base((float) ($rate->data->CSC_BTC->last * $this->getBitcoinPrice()), 'USD');
    }

}
