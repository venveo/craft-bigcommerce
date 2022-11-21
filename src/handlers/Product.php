<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\handlers;

use venveo\bigcommerce\Plugin;

class Product
{
    public const PRODUCT_UPDATE = 'store/product/updated';
    public const PRODUCT_CREATE = 'store/product/created';
    public const PRODUCT_DELETE = 'store/product/deleted';

    public function handle(string $scope, string $storeId, array $body): void
    {
        switch ($scope) {
            case static::PRODUCT_UPDATE:
            case static::PRODUCT_CREATE:
                Plugin::getInstance()->getProducts()->syncProductByBcId($body['id']);
                break;
            case static::PRODUCT_DELETE:
                Plugin::getInstance()->getProducts()->deleteProductByBcId($body['id']);
                break;
        }
    }
}
