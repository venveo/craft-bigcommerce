<?php
namespace venveo\bigcommerce\controllers;

use BigCommerce\ApiV3\ResourceModels\Customer\CustomerAddress;
use venveo\bigcommerce\api\operations\Customer;
use venveo\bigcommerce\base\BigCommerceApiController;
use venveo\bigcommerce\Plugin;

class AddressesController extends BigCommerceApiController
{
    public $enableCsrfValidation = true;
    public array|bool|int $allowAnonymous = ['save', 'delete'];

    public const CHANNEL_ID = 1;

    public function actionSave()
    {
        $this->requirePostRequest();
        $this->requireCustomer();
        $isNew = false;
        $addressId = $this->request->getBodyParam('addressId');
        if ($addressId) {
            $address = Plugin::getInstance()->getAddresses()->getAddressesForCurrentCustomer($addressId)[0] ?? null;
            if (!$address) {
                $this->asFailure('Failed to save address. Please try again.');
            }
        } else {
            $address = new CustomerAddress();
            $address->customer_id = Customer::getCurrentCustomerId();
        }
        $values = [
            'first_name' => $this->request->getBodyParam('first_name'),
            'last_name' => $this->request->getBodyParam('last_name'),
            'phone' => $this->request->getBodyParam('phone'),
            'address1' => $this->request->getBodyParam('address1'),
            'address2' => $this->request->getBodyParam('address2'),
            'city' => $this->request->getBodyParam('city'),
            'state_or_province' => $this->request->getBodyParam('state_or_province'),
            'postal_code' => $this->request->getBodyParam('postal_code'),
            'country_code' => $this->request->getBodyParam('country_code')
        ];
        foreach ($values as $key => $value) {
            if ($value) {
                $address->$key = $value;
            }
        }
        try {
            Plugin::getInstance()->getAddresses()->saveAddress($address, $addressId);
        } catch (\Exception $exception) {
            return $this->asFailure('Failed to save address.');
        }
        return $this->asSuccess('Address updated');
    }

    public function actionDelete()
    {
        $this->requirePostRequest();
        $this->requireCustomer();
        $addressId = (int)$this->request->getRequiredBodyParam('addressId');

        $address = Plugin::getInstance()->getAddresses()->getAddressesForCurrentCustomer($addressId)[0] ?? null;
        if (!$address) {
            $this->asFailure('Failed to save address. Please try again.');
        }

        try {
            $result = Plugin::getInstance()->getApi()->getClient()->customers()->addresses()->delete([$addressId]);
        } catch (\Exception $exception) {
            return $this->asFailure('Failed to delete address.');
        }
        return $this->asSuccess('Address deleted');
    }
}
