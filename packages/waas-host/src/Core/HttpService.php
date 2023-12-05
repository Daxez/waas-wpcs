<?php

namespace WaaSHost\Core;

use Exception;

class HttpService
{
    private string $auth_keys;
    private string $base_uri;

    public function __construct(ConfigService $config_service)
    {
        $this->base_uri = 'https://api.' . $config_service->get_api_region() . '.wpcs.io';
        $this->auth_keys = $config_service->get_api_key() . ":" . $config_service->get_api_secret();
    }

    /**
     * @throws Exception
     */
    public function get($uri)
    {
        $response = wp_remote_get($this->base_uri . $uri, [
            'headers' => $this->get_api_header()
        ]);

        $this->ensureSuccessStatusCode($response);
        return json_decode($response['body']);
    }

    /**
     * @throws Exception
     */
    public function getraw($uri)
    {
        $response = wp_remote_get($this->base_uri . $uri, [
            'headers' => $this->get_api_header()
        ]);

        // Skip ensureSuccessStatusCode and let caller handle

        return $response;
    }

    private function get_api_header(): array
    {
        if ($this->auth_keys === '') {
            return [
                'Content-Type' => 'application/json',
            ];
        }

        return [
            'Content-Type' => 'application/json',
            'Authorization' => "Basic " . base64_encode($this->auth_keys),
        ];
    }

    /**
     * @throws Exception
     */
    public function post($uri, $data)
    {
        $response = wp_remote_post($this->base_uri . $uri, [
            'method' => 'POST',
            'headers' => $this->get_api_header(),
            'body' => json_encode($data),
            'timeout' => 20,
        ]);

        $this->ensureSuccessStatusCode($response);
        return json_decode($response['body']);
    }

    /**
     * @throws Exception
     */
    public function put($uri, $data)
    {
        $response = wp_remote_post($this->base_uri . $uri, [
            'method' => 'PUT',
            'headers' => $this->get_api_header(),
            'body' => json_encode($data),
            'timeout' => 20,
        ]);

        $this->ensureSuccessStatusCode($response);
        return json_decode($response['body']);
    }

    /**
     * @throws Exception
     */
    public function delete($uri)
    {
        $response = wp_remote_get($this->base_uri . $uri, [
            'method' => 'DELETE',
            'headers' => $this->get_api_header(),
            'timeout' => 20,
        ]);

        $this->ensureSuccessStatusCode($response);
        return json_decode($response['body']);
    }

    private function ensureSuccessStatusCode($response)
    {
        if (is_wp_error($response)) {
            throw new Exception("Failed: " . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);

        // Only accept truly successful status codes 
        if ($response_code < 200 || $response_code > 299) {
            $response_body = wp_remote_retrieve_body($response);
            throw new Exception("Failed: " . $response_code . " - " . $response_body);
        }
    }
}
