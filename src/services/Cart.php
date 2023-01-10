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
class Cart extends Component
{
    use SdkClientTrait;

    public function getCart($create = true) {
        return \venveo\bigcommerce\api\operations\Cart::getCart($create);
    }
}
