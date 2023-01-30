<?php

namespace venveo\bigcommerce\api\operations;

use craft\helpers\Json;
use venveo\bigcommerce\base\ApiOperationInterface;
use venveo\bigcommerce\helpers\ApiHelper;
use venveo\bigcommerce\Plugin;
use yii\web\Cookie;

class Customer implements ApiOperationInterface
{
    public static $currentCustomerId = null;

    public static $currentCustomer = null;

    public static function login($email, $password, $setCookies = true): bool
    {
        $loginMutation = <<<'EOD'
mutation login($email: String!, $password: String!) {
    login(email: $email, password: $password) {
      result
    }
}
EOD;
        $response = ApiHelper::sendGraphQLRequest($loginMutation, compact('email', 'password'));
        $headers = $response->getHeaders();
        if ($setCookies && \Craft::$app->request instanceof \yii\web\Request && isset($headers['set-cookie'])) {
            $cookies = array_map(function ($rawCookie) {
                $cookieInfo = explode('=', $rawCookie, 2);
                $cookieDetails = explode(';', $cookieInfo[1]);
                $cookie = new Cookie();
                $cookie->name = $cookieInfo[0];
                $cookie->value = $cookieDetails[0];
                $cookie->secure = true;
                $cookie->httpOnly = true;
                $cookie->sameSite = Cookie::SAME_SITE_LAX;
                $cookie->expire = time() + 2592000; // 1 month
                return $cookie;
            }, $headers['set-cookie']);
            foreach ($cookies as $cookie) {
                \Craft::$app->response->cookies->add($cookie);
            }
        }
        $responseContents = json_decode($response->getBody()->getContents(), true);
        return isset($responseContents['data']['login']['result']) && $responseContents['data']['login']['result'] === "success";
    }

    public static function getCurrentCustomerId(): int|null
    {
        if (static::$currentCustomerId) {
            return static::$currentCustomerId;
        }
        if (static::$currentCustomerId === false) {
            return null;
        }
        $customerQuery = <<<'EOD'
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
        try {
            $response = ApiHelper::sendGraphQLRequest($customerQuery, null, true);
            $current = Json::decodeIfJson($response->getBody()->getContents())['data']['customer'] ?? null;
        } catch (\Exception $exception) {
            $current = null;
        }
        if ($current !== null) {
            static::$currentCustomerId = $current['entityId'];
        } else {
            static::$currentCustomerId = false;
        }
        return static::$currentCustomerId;
    }

    public static function getCurrentCustomer(): \BigCommerce\ApiV3\ResourceModels\Customer\Customer|null {
        $customerId = static::getCurrentCustomerId();
        if (!$customerId) {
            return null;
        }
        return static::$currentCustomer = Plugin::getInstance()->getApi()->getClient()->customers()->getById($customerId);
    }
}