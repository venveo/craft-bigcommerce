<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\handlers;

use venveo\bigcommerce\Plugin;
//use Shopify\Webhooks\Handler;
//use Shopify\Webhooks\Topics;

class Product implements Handler
{
    public function handle(string $topic, string $shop, array $body): void
    {
        switch ($topic) {
            case Topics::PRODUCTS_UPDATE:
            case Topics::PRODUCTS_CREATE:
                Plugin::getInstance()->getProducts()->syncProductByBcId($body['id']);
                break;
            case Topics::PRODUCTS_DELETE:
                Plugin::getInstance()->getProducts()->deleteProductByBcId($body['id']);
                break;
        }
    }
}
