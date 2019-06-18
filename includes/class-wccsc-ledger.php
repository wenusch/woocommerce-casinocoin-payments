<?php

class WCCSC_Ledger
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
            $node = 'https://csc-node-de-a.casinocoin.eu:5005';
        }

        if ($proxy === 'yes') {
            $this->node = 'https://cors-anywhere.herokuapp.com/' . $node;
            $this->headers = ['origin' => get_site_url()];
        } else {
            $this->node = $node;
        }
    }

    /**
     * Send an account_tx request to the specify casinocoind node.
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
     * Send an account_info request to the specify casinocoind node.
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

}
