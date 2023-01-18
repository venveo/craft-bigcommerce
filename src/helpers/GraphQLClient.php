<?php
namespace venveo\bigcommerce\helpers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GraphQLClient
{
    private $client;
    private $endpoint;
    private $access_token;

    public function __construct($endpoint, $access_token)
    {
        $this->client = new Client();
        $this->endpoint = $endpoint;
        $this->access_token = $access_token;
    }

    public function query($query, $variables = [])
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$this->access_token}",
        ];

        $data = [
            'query' => $query,
            'variables' => $variables,
        ];

        try {
            $response = $this->client->post($this->endpoint, [
                'headers' => $headers,
                'json' => $data
            ]);
            $responseBody = json_decode($response->getBody()->getContents(), true);
            if (isset($responseBody["errors"])) {
                throw new \RuntimeException("GraphQL Error: " . json_encode($responseBody["errors"]));
            } else {
                return $responseBody["data"];
            }
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                throw new \RuntimeException($e->getResponse()->getBody()->getContents(), $e->getResponse()->getStatusCode());
            } else {
                throw new \RuntimeException($e->getMessage());
            }
        }
    }

    public function mutation($mutation, $variables = [])
    {
        return $this->query($mutation, $variables);
    }
}
