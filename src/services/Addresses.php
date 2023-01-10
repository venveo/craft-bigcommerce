<?php

namespace venveo\bigcommerce\services;

use craft\base\Component;
use venveo\bigcommerce\api\operations\Customer;
use venveo\bigcommerce\Plugin;
use yii\web\ForbiddenHttpException;

class Addresses extends Component
{
    /**
     * @return \BigCommerce\ApiV3\ResourceModels\Customer\CustomerAddress[]
     */
    public function getAddressesForCurrentCustomer($addressId = null)
    {
        $customerId = Customer::getCurrentCustomerId();
        if (!$customerId) {
            throw new ForbiddenHttpException('You must be logged in to perform this action');
        }
        $filters = ['customer_id:in' => $customerId];
        if ($addressId) {
            $filters['id:in'] = $addressId;
        }

        return Plugin::getInstance()->getApi()->getClient()->customers()->addresses()->getAll($filters)->getAddresses();
    }

    public function saveAddress($properties = [])
    {

    }
}
