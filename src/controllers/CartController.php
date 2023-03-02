<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use BigCommerce\ApiV3\ResourceModels\Cart\CartItem;
use BigCommerce\ApiV3\ResponseModels\Cart\CartRedirectUrlsResponse;
use craft\helpers\ArrayHelper;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use venveo\bigcommerce\api\operations\Cart;
use venveo\bigcommerce\api\operations\Customer;
use venveo\bigcommerce\base\BigCommerceApiController;
use venveo\bigcommerce\Plugin;

class CartController extends BigCommerceApiController
{
    public $enableCsrfValidation = false;
    public array|bool|int $allowAnonymous = ['add', 'delete-line-item', 'update-line-item', 'checkout'];


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
        if (!isset($cart, $cart->line_items, $cart->line_items->physical_items)) {
            return $this->asFailure('You have no items in your cart.');
        }
        /** @var  $lineItem */
        $lineItem = ArrayHelper::firstWhere($cart->line_items->physical_items, 'id', $itemId);
        if (!$lineItem) {
            return $this->asFailure('Item not found in your cart.');
        }

        try {
            // Only quantity and list price are supported right now, so obviously we only want to allow updating quantity
            $quantity = $itemData['quantity'] ?? null;
            if ($quantity) {
                $lineItem->quantity = $quantity;
                $resource = Plugin::getInstance()->api->getClient()->cart($cart->id)->item($itemId);
                $response = $resource->getClient()->getRestClient()->put(
                    $resource->singleResourceUrl(),
                    [
                        RequestOptions::JSON => [
                            'line_item' => $lineItem
                        ],
                        RequestOptions::QUERY => [],


                    ]
                );
                return $this->asSuccess('Item updated successfully');
            } else {
                return $this->asFailure('Please specify a quantity');
            }
        } catch (ClientException $exception) {
            $contents = $exception->getResponse()->getBody()->getContents();
            return $this->asFailure('Failed to update line item');
        }
    }

    public function actionCheckout()
    {
        $cart = Cart::getCart(true);
        $customerId = Customer::getCurrentCustomerId();
        $redirectUrlsResponse = new CartRedirectUrlsResponse(Plugin::getInstance()->api->getClient()->getRestClient()->post(sprintf('carts/%s/redirect_urls',
            $cart->id)));
        $checkoutUrl = $redirectUrlsResponse->getCartRedirectUrls()->checkout_url;
        // TODO: Should a customer be allowed to take over another customer's cart?
//        $cart->customer_id === $customerId
        if ($customerId) {
            $checkoutUrl = Plugin::getInstance()->api->getCustomerLoginUrl($customerId, $checkoutUrl, null, 1);
        }

        return $this->redirect($checkoutUrl);
    }
}
