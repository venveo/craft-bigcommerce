<?php

namespace venveo\bigcommerce\api\operations;

use GuzzleHttp\Exception\ClientException;
use venveo\bigcommerce\base\ApiOperationInterface;
use venveo\bigcommerce\Plugin;
use yii\web\Cookie;
use yii\web\Request;

class Cart implements ApiOperationInterface
{
    public static function getCart($create, $redirectUrl = null): ?\BigCommerce\ApiV3\ResourceModels\Cart\Cart
    {
        $cartCookie = \Craft::$app->request->getCookies()->get('bc_cartId')?->value;
        $client = Plugin::getInstance()->getApi()->getClient();
        $channelId = Plugin::getInstance()->settings->getDefaultChannel(true);
        $cart = null;
        if ($cartCookie) {
            try {
                $cart = $client->cart($cartCookie)->get()?->getCart();
            } catch (ClientException) {
                if (\Craft::$app->request instanceof Request) {
                    \Craft::$app->response->cookies->remove('bc_cartId');
                }
                $cart = null;
            }
        }
        if (!$cart && $create) {
            $customerId = Customer::getCurrentCustomerId();
            $cartModel = new \BigCommerce\ApiV3\ResourceModels\Cart\Cart();
            $cartModel->channel_id = $channelId;
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