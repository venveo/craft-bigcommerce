<?php

namespace venveo\bigcommerce\services;

use BigCommerce\ApiV3\ResourceModels\Customer\CustomerAddress;
use craft\base\Component;
use craft\helpers\Json;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use venveo\bigcommerce\api\operations\Customer;
use venveo\bigcommerce\base\SdkClientTrait;
use yii\web\ForbiddenHttpException;

class Addresses extends Component
{
    use SdkClientTrait;

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

        return $this->getClient()->customers()->addresses()->getAll($filters)->getAddresses();
    }

    public function saveAddress(
        CustomerAddress $address,
        int $addressId = null
    ): \BigCommerce\ApiV3\ResponseModels\Customer\CustomerAddressesResponse {
        $isNew = $addressId === null;
        if ($isNew) {
            $result = $this->getClient()->customers()->addresses()->create([$address]);
        } else {
            $result = $this->getClient()->customers()->addresses()->update([$address]);
        }
        return $result;
    }

    public function getCountries(): Collection
    {
        $countries = new Collection(Json::decodeIfJson($this->getV2Client()->getRestClient()->get('countries', [
            RequestOptions::QUERY => [
                'page' => 1,
                'limit' => 250,
            ]
        ])->getBody()->getContents()));
        return $countries;
    }

    public function getStates(?int $countryId = null): Collection
    {
        $uri = 'countries/states';
        if ($countryId) {
            $uri = "countries/$countryId/states";
        }
        $allStates = new Collection(Json::decodeIfJson($this->getV2Client()->getRestClient()->get($uri, [
            RequestOptions::QUERY => [
                'page' => 1,
                'limit' => 250,
            ]
        ])->getBody()->getContents()));

        return $allStates;
    }
}
