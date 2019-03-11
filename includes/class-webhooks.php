<?php

class Webhook {

    protected $pub;
    protected $secret;
    public $base = 'https://webhook.xrpayments.co/';
    public $error = null;

    public function __construct( $pub, $secret ) {
        $this->pub = $pub;
        $this->secret = $secret;
    }


    public function subscriptions() {
        if ( empty( $this->pub ) || empty( $this->secret ) ) {
            $this->error = 'Please specify both the API key and secret';
            return false;
        }

        if ( ! ( $ch = curl_init( $this->base . 'api/v1/subscriptions' ) ) ) {
            $this->error = 'Unable to initiate cURL';
            return false;
        }

        $headers = array(
            'x-api-key: ' . $this->pub,
            'x-api-secret: ' . $this->secret
        );

        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_HTTPGET, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        $data = curl_exec( $ch );
        $info = curl_getinfo( $ch );

        if ( $info['http_code'] !== 200 || ( $res = json_decode( $data ) ) == null ) {
            $this->error = 'Unable to talk to the webhook API endpoint. Please verify that you\'ve specified the correct API key and secret.';
            return false;
        }

        return $res->subscriptions;
    }


    public function add_subscription( $address ) {
        if ( empty( $address ) || empty( $this->pub ) || empty( $this->secret ) ) {
            $this->error = 'Please specify both the API key and secret';
            return false;
        }

        if ( ! ( $ch = curl_init( $this->base . 'api/v1/subscriptions' ) ) ) {
            $this->error = 'Unable to initiate cURL';
            return false;
        }

        $headers = array(
            'Content-Type: application/json; charset=utf-8',
            'x-api-key: ' . $this->pub,
            'x-api-secret: ' . $this->secret
        );
        $payload = array(
            'address' => trim( $address )
        );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $payload ) );

        $data = curl_exec( $ch );
        $info = curl_getinfo( $ch );

        if ( $info['http_code'] !== 200 || ( $res = json_decode( $data ) ) == null ) {
            $this->error = 'Unable to talk to the webhook API endpoint. Please verify that you\'ve specified the correct API key and secret.';
            return false;
        }

        return $res->subscription_id;
    }


    public function webhooks() {
        if ( empty( $this->pub ) || empty( $this->secret ) ) {
            $this->error = 'Please specify both the API key and secret';
            return false;
        }

        if ( ! ($ch = curl_init( $this->base . 'api/v1/webhooks' )) ) {
            $this->error = 'Unable to initiate cURL';
            return false;
        }

        $headers = array(
            'x-api-key: ' . $this->pub,
            'x-api-secret: ' . $this->secret
        );

        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_HTTPGET, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        $data = curl_exec( $ch );
        $info = curl_getinfo( $ch );

        if ( $info['http_code'] !== 200 || ( $res = json_decode( $data ) ) == null ) {
            $this->error = 'Unable to talk to the webhook API endpoint. Please verify that you\'ve specified the correct API key and secret.';
            return false;
        }

        return $res->webhooks;
    }


    public function add_webhook( $url ) {
        if ( empty( $url ) || empty( $this->pub ) || empty( $this->secret ) ) {
            $this->error = 'Please specify both the API key and secret';
            return false;
        }

        if ( ! ( $ch = curl_init( $this->base . 'api/v1/webhooks' ) ) ) {
            $this->error = 'Unable to initiate cURL';
            return false;
        }

        $headers = array(
            'Content-Type: application/json; charset=utf-8',
            'x-api-key: ' . $this->pub,
            'x-api-secret: ' . $this->secret
        );
        $payload = array(
            'url' => trim( $url )
        );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $payload ) );

        $data = curl_exec( $ch );
        $info = curl_getinfo( $ch );

        if ( $info['http_code'] !== 200 || ( $res = json_decode( $data ) ) == null ) {
            $this->error = 'Unable to talk to the webhook API endpoint. Please verify that you\'ve specified the correct API key and secret.';
            return false;
        }

        return $res->webhook_id;
    }
}
