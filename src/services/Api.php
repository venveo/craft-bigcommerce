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
use GuzzleHttp\Exception\ClientException;
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
}
