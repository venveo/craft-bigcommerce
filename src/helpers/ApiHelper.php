<?php

namespace venveo\bigcommerce\helpers;

use venveo\bigcommerce\Plugin;

class ApiHelper
{
    public static function getApiToken()
    {
        $expiresIn = 60 * 60 * 24 * 30;
        return \Craft::$app->cache->getOrSet('bigcommerce.api.token', function () use ($expiresIn) {
            $client = Plugin::getInstance()->getApi()->getClient()->getRestClient();
            $response = $client->post('storefront/api-token', [
                'json' => [
                    'channel_id' => 1,
                    'expires_at' => time() + $expiresIn
                ]
            ]);
            return json_decode($response->getBody()->getContents(), true)['data']['token'];
        }, $expiresIn - 60);
    }

    public static function getClient($config): \GuzzleHttp\Client
    {
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

    public static function sendGraphQLRequest(string $query = null, $variables = [], $asCustomer = false)
    {
        $apiToken = static::getApiToken();
        $client = static::getGqlClient();
        $body = array_filter([
            'query' => $query,
            'variables' => $variables
        ]);
        $headers = [];
        $options = [
            'json' => $body
        ];
        if ($asCustomer && $cookie = \Craft::$app->request->getCookies()->get('SHOP_TOKEN')) {
            if ($cookie->value) {
                $headers['cookie'] = 'SHOP_TOKEN=' . $cookie->value;
            }
        }
        $headers['Authorization'] = 'Bearer ' . $apiToken;
        $options['headers'] = $headers;
        $resp = $client->post('/graphql', $options);
        return $resp;
    }
}