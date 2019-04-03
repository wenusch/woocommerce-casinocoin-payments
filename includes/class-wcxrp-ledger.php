<?php

class WCXRP_Ledger
{
    private $node = false;
    private $headers = [];

    /**
     * Ledger constructor.
     * @param $node
     * @param $proxy
     */
    function __construct($node, $proxy=null)
    {
        if (empty($node)) {
            $node = 'https://s2.ripple.com:51234';
        }

        if ($proxy === 'yes') {
            $this->node = 'https://cors-anywhere.herokuapp.com/' . $node;
            $this->headers = ['origin' => get_site_url()];
        } else {
            $this->node = $node;
        }
    }

    /**
     * Send an account_tx request to the specify rippled node.
     * @param $account
     * @param $limit
     * @return bool|object
     */
    function account_tx($account, $limit = 10)
    {
        $payload = json_encode([
            'method' => 'account_tx',
            'params' => [[
                'account' => $account,
                'ledger_index_min' => -1,
                'ledger_index_max' => -1,
                'limit' => $limit,
            ]]
        ]);

        $res = wp_remote_post($this->node, [
            'body' => $payload,
            'headers' => $this->headers
        ]);
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($data = json_decode($res['body'])) === null) {
            return false;
        }

        return array_reverse($data->result->transactions);
    }

    /**
     * Send an account_info request to the specify rippled node.
     * @param $account
     * @return bool
     */
    function account_info($account)
    {
        $payload = json_encode([
            'method' => 'account_info',
            'params' => [[
                'account' => $account
            ]]
        ]);

        $res = wp_remote_post($this->node, [
            'body' => $payload,
            'headers' => $this->headers
        ]);
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($data = json_decode($res['body'])) === null) {
            return false;
        }

        return $data->result;
    }

    public function book_offers($account, $currency)
    {
        $taker_pays['currency'] = $currency;
        if ($currency === 'USD') {
            $taker_pays['issuer'] = 'rvYAfWj5gh67oV6fW32ZzP3Aw4Eubs59B';
        } elseif ($currency === 'EUR') {
            $taker_pays['issuer'] = 'rhub8VRN55s94qWKDv6jmDy1pUykJzF3wq';
        } else {
            return false;
        }

        $payload = json_encode([
            'method' => 'book_offers',
            'params' => [[
                'taker' => $account,
                "taker_gets" => [
                    "currency" => "XRP"
                ],
                "taker_pays" => $taker_pays,
                "limit" => 50
            ]]
        ]);

        $res = wp_remote_post($this->node, [
            'body' => $payload,
            'headers' => $this->headers
        ]);
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($data = json_decode($res['body'])) === null) {
            return false;
        }

        foreach ($data->result->offers as $offer) {
            if (!isset($offer->owner_funds)) {
                continue;
            }

            $rate = $offer;
            break;
        }

        return $rate->TakerPays->value / ($rate->TakerGets / 1000000);
    }
}
