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
use venveo\bigcommerce\base\SdkClientTrait;
use venveo\bigcommerce\Plugin;

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

    use SdkClientTrait;


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
     * Retrieve a single product by its BigCommerce ID.
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
//        return $this->getAll(Metafield::class, [
//            'metafield' => [
//                'owner_id' => $id,
//                'owner_resource' => 'product',
//            ],
//        ]);
    }

    public function getVariantsByProductId(int $id): array
    {
        $variants = $this->getClient()->catalog()->product($id)->variants()->getAll()->getProductVariants();
        return $variants;
    }

    public function getOptionsByProductId(int $id): array
    {
        $options = $this->getClient()->catalog()->product($id)->options()->getAll()->getOptions();
        return $options;
    }

    /**
     * @throws GuzzleException
     */
    public function getServerTime(): int
    {
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
}
