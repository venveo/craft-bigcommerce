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
use craft\log\MonologTarget;
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
//     * @var Session|null
     */
//    private ?Session $_session = null;

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
     * Retrieves "metafields" for the provided Shopify product ID.
     *
     * @param int $id Shopify Product ID
     */
    public function getMetafieldsByProductId(int $id): array
    {
        return [];
//        return $this->getAll(ShopifyMetafield::class, [
//            'metafield' => [
//                'owner_id' => $id,
//                'owner_resource' => 'product',
//            ],
//        ]);
    }

    /**
     * Shortcut for retrieving arbitrary API resources. A plain (parsed) response body is returned, so it’s the caller’s responsibility for unpacking it properly.
     *
     * @see Rest::get();
     */
    public function get($path, array $query = [])
    {
        $response = $this->getClient()->get($path, [], $query);

        return $response->getDecodedBody();
    }
//
//    /**
//     * Iteratively retrieves a paginated collection of API resources.
//     *
//     * @param string $type Stripe API resource class
//     * @param array $params
//     * @return ShopifyBaseResource[]
//     */
//    public function getAll(string $type, array $params = []): array
//    {
//        $resources = [];
//
//        // Force maximum page size:
//        $params['limit'] = 250;
//
//        do {
//            $resources = array_merge($resources, $type::all(
//                $this->getSession(),
//                [],
//                $type::$NEXT_PAGE_QUERY ?: $params,
//            ));
//        } while ($type::$NEXT_PAGE_QUERY);
//
//        return $resources;
//    }

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

//
//    /**
//     * Returns or initializes a context + session.
//     *
//     * @return Session|null
//     * @throws \Shopify\Exception\MissingArgumentException
//     */
//    public function getSession(): ?Session
//    {
//        $pluginSettings = Plugin::getInstance()->getSettings();
//
//        if (
//            $this->_session === null &&
//            ($apiClientId = App::parseEnv($pluginSettings->clientId)) &&
//            ($apiSecretKey = App::parseEnv($pluginSettings->clientSecret))
//        ) {
//            /** @var MonologTarget $webLogTarget */
//            $webLogTarget = Craft::$app->getLog()->targets['web'];
//            Context::initialize(
//                apiKey: $apiKey,
//                apiSecretKey: $apiSecretKey,
//                scopes: ['write_products', 'read_products'],
//                // This `hostName` is different from the `shop` value used when creating a Session!
//                // Shopify wants a name for the host/environment that is initiating the connection.
//                hostName: !Craft::$app->request->isConsoleRequest ? Craft::$app->getRequest()->getHostName() : 'localhost',
//                sessionStorage: new FileSessionStorage(Craft::$app->getPath()->getStoragePath() . DIRECTORY_SEPARATOR . 'bigcommerce_api_sessions'),
//                apiVersion: self::SHOPIFY_API_VERSION,
//                isEmbeddedApp: false,
//                logger: $webLogTarget->getLogger()
//            );
//
////
////            $this->_session = new Session(
////                id: 'NA',
////                shop: $storeHash,
////                isOnline: false,
////                state: 'NA'
////            );
//
////            $this->_session->setAccessToken($accessToken); // this is the most important part of the authentication
//        }
//
//        return $this->_session;
//    }
}
