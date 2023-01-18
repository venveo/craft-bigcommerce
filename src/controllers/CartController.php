<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use BigCommerce\ApiV3\ResourceModels\Cart\CartItem;
use BigCommerce\ApiV3\ResponseModels\Cart\CartRedirectUrlsResponse;
use venveo\bigcommerce\api\operations\Cart;
use venveo\bigcommerce\base\BigCommerceApiController;
use venveo\bigcommerce\Plugin;

class CartController extends BigCommerceApiController
{
    public $enableCsrfValidation = false;
    public array|bool|int $allowAnonymous = ['add', 'delete-line-item', 'update-line-item', 'checkout'];

    public const CHANNEL_ID = 1;

    public function actionAdd()
    {
        $this->enableCsrfValidation = false;
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $cart = Cart::getCart(true);
        $cartItem = new CartItem();
        $cartItem->line_items = $this->request->getRequiredBodyParam('line_items');
        try {
            Plugin::getInstance()->api->getClient()->cart($cart->id)->items()->add($cartItem);
            return $this->asSuccess('Item added to cart successfully');
        } catch (\Exception $exception) {
            return $this->asFailure('Failed to add item to cart');
        }
    }

    public function actionDeleteLineItem()
    {
        $this->enableCsrfValidation = false;
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $cart = Cart::getCart(true);
        $itemId = $this->request->getRequiredBodyParam('id');
        try {
            Plugin::getInstance()->api->getClient()->cart($cart->id)->item($itemId)->delete();
            return $this->asSuccess('Item deleted successfully');
        } catch (\Exception $exception) {
            return $this->asFailure('Failed to add item to cart');
        }
    }

    public function actionUpdateLineItem()
    {
        $this->enableCsrfValidation = false;
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $cart = Cart::getCart(true);
        $itemId = $this->request->getRequiredBodyParam('id');
        $itemData = $this->request->getRequiredBodyParam('item');
        try {
            Plugin::getInstance()->api->getClient()->cart($cart->id)->item($itemId)->update($itemData);
            return $this->asSuccess('Item updated successfully');
        } catch (\Exception $exception) {
            return $this->asFailure('Failed to update line item');
        }
    }

    public function actionCheckout() {
        $cart = Cart::getCart(true);

        $redirectUrlsResponse = new CartRedirectUrlsResponse(Plugin::getInstance()->api->getClient()->getRestClient()->post(sprintf('carts/%s/redirect_urls', $cart->id)));
        return $this->redirect($redirectUrlsResponse->getCartRedirectUrls()->checkout_url);
    }
}
