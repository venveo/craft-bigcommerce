<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use craft\helpers\App;
use craft\web\Controller;
use Firebase\JWT\JWT;
use venveo\bigcommerce\models\bigcommerce\CreateCustomerRequest;
use venveo\bigcommerce\Plugin;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;

class CustomersController extends Controller
{
    public $enableCsrfValidation = true;
    public array|bool|int $allowAnonymous = ['register'];

    public const CHANNEL_ID = 1;

    /**
     * @throws InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function actionRegister(): ?\yii\web\Response
    {
        $this->requirePostRequest();
        $client = Plugin::getInstance()->getApi()->getClient();
        $request = new CreateCustomerRequest();
        $request->attributes = \Craft::$app->request->getBodyParams();
        $request->channel_ids = [static::CHANNEL_ID];
        $request->customer_group_id = 2;

        if (!$request->validate()) {
            return $this->asModelFailure($request, null, 'customer');
        }
        // Everything else passed, now we can check with the BC API to see if the account exists.
        $request->setScenario(CreateCustomerRequest::SCENARIO_VERIFY_ACCOUNT);
        if (!$request->validate()) {
            return $this->asModelFailure($request, null, 'customer');
        }
        try {
            $customer = $client->customers()->create([$request->createPayload()])->getCustomers()[0];
            $token = Plugin::getInstance()->getApi()->getCustomerLoginToken($customer->id, channelId: static::CHANNEL_ID);
            \Craft::dd($token);
        } catch (\Exception $e) {
            return $this->asModelFailure($request, 'Failed to create customer: '. $e->getMessage(), 'customer');
        }
        return $this->asSuccess('You have been successfully logged in');
    }
}
