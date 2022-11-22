<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\services;

use BigCommerce\ApiV3\Client;
use BigCommerce\ApiV3\ResourceModels\Catalog\Product\Product;
use Craft;
use craft\base\Component;
use craft\helpers\App;
use Firebase\JWT\JWT;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use venveo\bigcommerce\Plugin;
use Shopify\Auth\FileSessionStorage;
use Shopify\Auth\Session;
use Shopify\Clients\Rest;
use Shopify\Context;

/**
 * BigCommerce API service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 *
 *
 * @property-read void $products
 */
class Api extends Component
{
    /**
     * @var Client|null
     */
    private ?Client $_client = null;

    /**
     * Retrieve all a shop’s products.
     *
     * @return Product[]
     */
    public function getAllProducts(): array
    {
        return $this->getClient()->catalog()->products()->getAllPages()->getProducts();
    }

    /**
     * Retrieve a single product by its Shopify ID.
     *
     * @return Product
     */
    public function getProductByBcId($id): Product
    {
        return $this->getClient()->catalog()->product($id)->get()->getProduct();
    }

    /**
     * Retrieves "metafields" for the provided BigCommerce product ID.
     *
     * @param int $id BigCommerce Product ID
     */
    public function getMetafieldsByProductId(int $id): array
    {
        try {
            return $this->getClient()->catalog()->product($id)->metafields()->getAll()->getMetafields();
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return [];
            }
            throw $e;
        }
//        return [];
//        return $this->getAll(ShopifyMetafield::class, [
//            'metafield' => [
//                'owner_id' => $id,
//                'owner_resource' => 'product',
//            ],
//        ]);
    }

    public function getVariantsByProductId(int $id): array {
        $variants = $this->getClient()->catalog()->product($id)->variants()->getAll()->getProductVariants();
        return $variants;
    }

    /**
     * @throws GuzzleException
     */
    public function getServerTime(): int {
        $offset = Craft::$app->cache->get('bigcommerce_time_offset');
        if ($offset === null) {
            $offset = $this->updateServerTime();
        }
        return time() + $offset;
    }

    /**
     * @return int server time offset
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateServerTime(): int
    {
        try {
            $apiTime = $this->getClient()->getRestClient()->get('/time')->getBody()->getContents();
        } catch (\Exception $e) {
            $apiTime = time();
        }
        $now = time();
        $offset = $apiTime - $now;
        Craft::$app->cache->set('bigcommerce_time_offset', $offset);
        return $offset;
    }

    /**
     * Returns or sets up a Rest API client.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        if ($this->_client === null) {
            $pluginSettings = Plugin::getInstance()->getSettings();
            $apiClientId = App::parseEnv($pluginSettings->clientId);
            $apiSecretKey = App::parseEnv($pluginSettings->clientSecret);
            $storeHash = App::parseEnv($pluginSettings->storeHash);
            $accessToken = App::parseEnv($pluginSettings->accessToken);

            $this->_client = new \BigCommerce\ApiV3\Client($storeHash, $apiClientId, $accessToken);
        }

        return $this->_client;
    }

    public function getCustomerLoginToken(int $id, $redirectUrl = null, $requestIp = null, $channelId = null): string
    {
        $settings = Plugin::getInstance()->getSettings();
        $jwtPayload = [
            'iss' => App::parseEnv($settings->clientId),
            'iat' => Plugin::getInstance()->getApi()->getServerTime(),
            'jti'         => bin2hex( random_bytes( 32 ) ),
            'store_hash' => App::parseEnv($settings->storeHash),
            'customer_id' => $id
        ];

        if (!empty($redirectUrl)) {
            $jwtPayload['redirect_to'] = $redirectUrl;
        }

        if (!empty($requestIp)) {
            $jwtPayload['request_ip'] = $requestIp;
        }

        if (!empty($channelId)) {
            $jwtPayload['channel_id'] = (int)$channelId;
        }

        $secret = App::parseEnv($settings->clientSecret);
        return JWT::encode($jwtPayload, $secret, 'HS256');
    }
}
