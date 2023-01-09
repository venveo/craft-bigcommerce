<?php

namespace venveo\bigcommerce\api\operations\customers;

use craft\helpers\Json;
use venveo\bigcommerce\helpers\ApiHelper;

class GetCurrentCustomer
{
    public const QUERY = <<<'EOD'
query {
  customer {
    entityId
    company
    customerGroupId
    email
    firstName
    lastName
    phone
  }
}
EOD;

    public static function getCurrentCustomer()
    {
        $response = ApiHelper::sendGraphQLRequest(static::QUERY, null, true);
        return Json::decodeIfJson($response->getBody()->getContents())['data']['customer'] ?? null;
    }
}