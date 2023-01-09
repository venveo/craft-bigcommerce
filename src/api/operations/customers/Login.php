<?php

namespace venveo\bigcommerce\api\operations\customers;

use venveo\bigcommerce\helpers\ApiHelper;
use yii\web\Cookie;

class Login
{
    public const LOGIN_MUTATION = <<<'EOD'
mutation login($email: String!, $password: String!) {
    login(email: $email, password: $password) {
      result
    }
}
EOD;

    public static function login($email, $password): bool
    {
        $response = ApiHelper::sendGraphQLRequest(static::LOGIN_MUTATION, ['email' => $email, 'password' => $password]);
        $headers = $response->getHeaders();
        if (\Craft::$app->request instanceof \yii\web\Request && isset($headers['set-cookie'])) {
            $cookies = array_map(function ($rawCookie) {
                $cookieInfo = explode('=', $rawCookie, 2);
                $cookieDetails = explode(';', $cookieInfo[1]);
                $cookie = new Cookie();
                $cookie->name = $cookieInfo[0];
                $cookie->value = $cookieDetails[1];
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
        if (isset($responseContents['data']['login']['result']) && $responseContents['data']['login']['result'] === "success") {
            return true;
        }
        return false;
    }
}