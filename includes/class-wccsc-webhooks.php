<?php

class WCCSC_Webhook
{
    protected $pub;
    protected $secret;
    public $base = 'https://webhook.casinocoin.eu/api/v1/';
    public $error = null;

    /**
     * Webhook constructor.
     * @param $pub
     * @param $secret
     */
    public function __construct($pub, $secret)
    {
        $this->pub = $pub;
        $this->secret = $secret;
    }

    /**
     * @return bool
     */
    public function subscriptions()
    {
        if (empty($this->pub) || empty($this->secret)) {
            return false;
        }

        $headers = [
            'x-api-key' => $this->pub,
            'x-api-secret' => $this->secret
        ];
        $res = wp_remote_get(
            $this->base . 'subscriptions',
            [
                'headers' => $headers
            ]
        );
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($data = json_decode($res['body'])) === null) {
            return false;
        }

        return $data->subscriptions;
    }

    /**
     * @param $address
     * @return bool
     */
    public function add_subscription($address)
    {
        if (empty($address) || empty($this->pub) || empty($this->secret)) {
            return false;
        }

        $headers = [
            'Content-Type' => 'application/json; charset=utf-8',
            'x-api-key' => $this->pub,
            'x-api-secret' => $this->secret
        ];
        $payload = json_encode(['address' => trim($address)]);

        $res = wp_remote_post(
            $this->base . 'subscriptions',
            [
                'headers' => $headers,
                'body' => $payload
            ]
        );
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($data = json_decode($res['body'])) === null) {
            return false;
        }

        return $data->subscription_id;
    }

    /**
     * @return bool
     */
    public function webhooks()
    {
        if (empty($this->pub) || empty($this->secret)) {
            return false;
        }
        $headers = [
            'x-api-key' => $this->pub,
            'x-api-secret' => $this->secret
        ];
        $res = wp_remote_get(
            $this->base . 'webhooks',
            [
                'headers' => $headers
            ]
        );
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($data = json_decode($res['body'])) === null) {
            return false;
        }

        return $data->webhooks;
    }

    /**
     * @param $url
     * @return bool
     */
    public function add_webhook($url)
    {
        if (empty($url) || empty($this->pub) || empty($this->secret)) {
            return false;
        }

        $headers = [
            'Content-Type' => 'application/json; charset=utf-8',
            'x-api-key' => $this->pub,
            'x-api-secret' => $this->secret
        ];
        $payload = json_encode(['url' => trim($url)]);
        $res = wp_remote_post(
            $this->base . 'webhooks',
            [
                'headers' => $headers,
                'body' => $payload
            ]
        );
        if (is_wp_error($res) || $res['response']['code'] !== 200) {
            return false;
        }
        if (($data = json_decode($res['body'])) === null) {
            return false;
        }

        return $data->webhook_id;
    }
}
