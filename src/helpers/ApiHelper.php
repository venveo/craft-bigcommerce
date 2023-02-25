<?php

namespace venveo\bigcommerce\helpers;

use JetBrains\PhpStorm\ArrayShape;
use venveo\bigcommerce\Plugin;

class ApiHelper
{
    public static function getApiToken()
    {
        $expiresIn = 60 * 60 * 24 * 30;
        $channelId = Plugin::getInstance()->settings->getDefaultChannel(true);
        return \Craft::$app->cache->getOrSet('bigcommerce.api.token.' . $channelId,
            function () use ($expiresIn, $channelId) {
                $client = Plugin::getInstance()->getApi()->getClient()->getRestClient();
                $response = $client->post('storefront/api-token', [
                    'json' => [
                        'channel_id' => $channelId,
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
        if ($asCustomer && $cookie = \Craft::$app->request->getCookies()?->get('SHOP_TOKEN')?->value) {
            // NOTE: Could also use header: X-Bc-Customer-Id: 123
            if ($cookie) {
                $headers['cookie'] = 'SHOP_TOKEN=' . $cookie;
            }
        }
        $headers['Authorization'] = 'Bearer ' . $apiToken;
        $options['headers'] = $headers;
        $resp = $client->post('/graphql', $options);
        return $resp;
    }


    /**
     * Process a Guzzle exception into its components based on the response body.
     *
     * @param \GuzzleHttp\Exception\ClientException $exception The Guzzle exception to process.
     *
     * @return array An associative array with the following keys: status, title, type, errors, summary.
     * The 'summary' key will contain a string with the first error message, if any.
     */
    #[ArrayShape([
        'status' => "int|mixed",
        'title' => "mixed|string",
        'type' => "mixed|string",
        'errors' => "array|mixed",
        'summary' => "null|string"
    ])] public static function processGuzzleException(\GuzzleHttp\Exception\ClientException $exception): array
    {
        $response = $exception->getResponse();
        $responseBody = $response->getBody()->getContents();
        $responseJson = json_decode($responseBody, true);

        $status = $responseJson['status'] ?? $response->getStatusCode();
        $title = $responseJson['title'] ?? 'Unknown error';
        $type = $responseJson['type'] ?? '';
        $errors = $responseJson['errors'] ?? [];

        $firstError = current(array_values($errors));
        $summary = is_string($firstError) ? $firstError : null;


        return [
            'status' => $status,
            'title' => $title,
            'type' => $type,
            'errors' => $errors,
            'summary' => $summary
        ];
    }
}