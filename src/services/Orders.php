<?php

namespace venveo\bigcommerce\services;

use BigCommerce\ApiV2\ResponseModels\Order\Order;
use craft\base\Component;
use venveo\bigcommerce\api\operations\Customer;
use venveo\bigcommerce\Plugin;
use yii\web\ForbiddenHttpException;

/**
 *
 * @property-read \BigCommerce\ApiV2\ResponseModels\Order\Order[] $ordersForCurrentCustomer
 */
class Orders extends Component
{
    /**
     * @return Order[]
     * @throws ForbiddenHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function getOrdersForCurrentCustomer(): array
    {
        $customerId = Customer::getCurrentCustomerId();
        if (!$customerId) {
            throw new ForbiddenHttpException('You must be logged in to perform this action');
        }
        $filters = ['customer_id' => $customerId];
        $client = Plugin::getInstance()->getApi()->getV2Client();
        try {
            return $client->orders()->getAll($filters);
        } catch (\TypeError $exception) {
            // Note: When there are no orders, the SDK blows up since the response is "null" instead of an empty array.
            return [];
        }
    }

    public function getOrderDetails(int $orderId, int $customerId = null): ?array
    {
        $client = Plugin::getInstance()->getApi()->getV2Client();
        $orderResource = $client->order($orderId);
        $order = $orderResource->get();
        if ($customerId && $order->customer_id !== $customerId) {
            return null;
        }
        $shipping_addresses = $orderResource->shippingAddresses()->getAll();
        $products = $orderResource->products()->getAll();

        return [
            'order' => $order,
            'shipping_addresses' => $shipping_addresses,
            'products' => $products
        ];
    }
}
