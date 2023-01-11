<?php

namespace venveo\bigcommerce\api\operations;

use craft\helpers\Json;
use venveo\bigcommerce\base\ApiOperationInterface;
use venveo\bigcommerce\helpers\ApiHelper;
use venveo\bigcommerce\Plugin;
use yii\web\Cookie;
use yii\web\Request;

class Cart implements ApiOperationInterface
{
    public static function getCart($create, $redirectUrl = null): ?\BigCommerce\ApiV3\ResourceModels\Cart\Cart
    {
        $cartCookie = \Craft::$app->request->getCookies()->get('bc_cartId')?->value;
        $client = Plugin::getInstance()->getApi()->getClient();
        $cart = null;
        if ($cartCookie) {
            $cart = $client->cart($cartCookie)->get()?->getCart();
        }
        if (!$cart && $create) {
            $customerId = Customer::getCurrentCustomerId();
            $cartModel = new \BigCommerce\ApiV3\ResourceModels\Cart\Cart();
            $cartModel->channel_id = 1;
            $cartModel->customer_id = $customerId;
            $cartModel->line_items = [];
            $cart = $client->carts()->create($cartModel)->getCart();
        }
        if ($cart && $cart->id && \Craft::$app->request instanceof Request) {
            $cookie = new Cookie();
            $cookie->name = 'bc_cartId';
            $cookie->value = $cart->id;
            $cookie->secure = true;
            $cookie->httpOnly = true;
            $cookie->sameSite = Cookie::SAME_SITE_LAX;
            $cookie->expire = time() + 2592000; // 1 month
            \Craft::$app->response->getCookies()->add($cookie);
        }
        return $cart;
    }

}