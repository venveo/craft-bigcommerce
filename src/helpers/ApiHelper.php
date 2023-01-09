<?php

namespace venveo\bigcommerce\helpers;

use craft\helpers\App;
use http\Client\Request;
use venveo\bigcommerce\Plugin;

class ApiHelper {
    public static function getApiToken() {
        $client = Plugin::getInstance()->getApi()->getClient()->getRestClient();
        $response = $client->post('storefront/api-token', [
            'json' => [
                'channel_id' => 1,
                'expires_at' => time() + 60*60*24*30
            ]
        ]);
        return json_decode($response->getBody()->getContents(), true)['data']['token'] ?? null;


    }
    public static function getClient($config): \GuzzleHttp\Client {
        return new \GuzzleHttp\Client($config);
    }
    public static function getGqlClient(): \GuzzleHttp\Client
    {
        $storeService = Plugin::getInstance()->getStore();
        $baseUrl = $storeService->getUrl('graphql');
        $client = static::getClient([
            'base_uri' => $baseUrl,
        ]);
        return $client;
    }

    public static function sendGraphQLRequest(string $query = null, $variables = []) {
        $apiToken = static::getApiToken();
        $client = static::getGqlClient();
        $body = array_filter([
                'query' => $query,
                'variables' => $variables
        ]);
        $headers = ['Authorization' => 'Bearer '. $apiToken];
        $resp = $client->post('/graphql', ['headers' => $headers, 'json' => $body]);
        return $resp;
    }
}