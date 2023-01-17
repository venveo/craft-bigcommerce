<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\services;

use craft\base\Component;
use venveo\bigcommerce\base\SdkClientTrait;

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

    public function getCart($create = true, $redirectUrl = null)
    {
        return \venveo\bigcommerce\api\operations\Cart::getCart($create, $redirectUrl);
    }
}
