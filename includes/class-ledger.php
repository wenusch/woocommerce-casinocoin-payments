<?php


class Ledger
{
    private $node = false;

    /**
     * Ugly helper to print pretty statuses.
     * @param $node
     */
    function __construct( $node, $proxy=null ) {
        if ( empty( $node ) ) {
            $node = 'https://s2.ripple.com:51234';
        }

        if ( $proxy === 'yes' ) {
            $this->node = 'https://cors-anywhere.herokuapp.com/' . $node;
            $headers = ['origin' => get_site_url()];
        } else {
            $this->node = $node;
            $headers = [];
        }
    }

    /**
     * Ugly helper to print pretty statuses.
     * @param $account
     * @param $limit
     * @return bool|object
     */
    function account_tx( $account, $limit = 10 ) {
        $payload = json_encode( [
            'method' => 'account_tx',
            'params' => [[
                'account' => $account,
                'ledger_index_min' => -1,
                'ledger_index_max' => -1,
                'limit' => $limit,
            ]]
        ] );

        $res = wp_remote_post( $this->node, array(
            'body' => $payload,
            'headers' => $headers
        ) );
        if ( is_wp_error( $res ) || $res['response']['code'] !== 200 ) {
            return false;
        }

        if ( ( $data = json_decode( $res['body'] ) ) == null ) {
            return false;
        }

        return array_reverse( $data->result->transactions );
    }

    /**
     * Check if the account is activated.
     * @param $account
     * @return bool
     */
    function account_info( $account ) {
        $payload = json_encode( [
            'method' => 'account_info',
            'params' => [[
                'account' => $account
            ]]
        ] );

        $res = wp_remote_post( $this->node, array(
            'body' => $payload,
            'headers' => $headers
        ) );
        if ( is_wp_error( $res ) || $res['response']['code'] !== 200 ) {
            return false;
        }

        if ( ( $data = json_decode( $res['body'] ) ) == null ) {
            return false;
        }

        return $data->result;
    }

}
