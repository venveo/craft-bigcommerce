<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use craft\helpers\UrlHelper;
use venveo\bigcommerce\api\operations\Customer;
use venveo\bigcommerce\base\BigCommerceApiController;
use venveo\bigcommerce\models\bigcommerce\CreateCustomerRequest;
use venveo\bigcommerce\Plugin;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

class CustomersController extends BigCommerceApiController
{
    public $enableCsrfValidation = true;
    public array|bool|int $allowAnonymous = ['register', 'login', 'save-profile'];

    public const CHANNEL_ID = 1;

    public function actionLogin()
    {
        $this->requirePostRequest();
        $currentCustomer = Customer::getCurrentCustomerId();
        if ($currentCustomer) {
            return $this->redirectToPostedUrl(null, UrlHelper::url('/store/account'));
        }
        $email = $this->request->getRequiredBodyParam('email');
        $password = $this->request->getRequiredBodyParam('password');
        $success = Customer::login($email, $password);
        if ($success) {
            return $this->redirectToPostedUrl();
        }
        return $this->asFailure('Incorrect username or password', ['email' => $email]);
    }

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
            $redirectUrl = $this->getPostedRedirectUrl();
            $loginUrl = Plugin::getInstance()->getApi()->getCustomerLoginUrl($customer->id,
                UrlHelper::siteUrl($redirectUrl, ['bc_login' => 1]));
            return $this->redirect($loginUrl);
        } catch (\Exception $e) {
            return $this->asModelFailure($request, 'Failed to create customer: ' . $e->getMessage(), 'customer');
        }
        return $this->asSuccess('You have been successfully logged in');
    }

    /**
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function actionSaveProfile()
    {
        $this->requirePostRequest();
        $this->requireCustomer();
        $customer = Customer::getCurrentCustomer();
        if (!$customer) {
            $this->response->setStatusCode(500);
            return $this->asFailure('Your customer record could not be located. Please sign out and sign back in.');
        }
        $firstName = $this->request->getBodyParam('firstName');
        $lastName = $this->request->getBodyParam('lastName');
        $email = $this->request->getBodyParam('email');
        $company = $this->request->getBodyParam('company');
        $phone = $this->request->getBodyParam('phone');
        $customer->first_name = $firstName;
        $customer->last_name = $lastName;
        $customer->email = $email;
        $customer->company = $company;
        $customer->phone = $phone;
        try {
            $resp = Plugin::getInstance()->getApi()->getClient()->customers()->update([$customer]);
        } catch (\Exception $exception) {
            \Craft::error($exception->getMessage());
            \Craft::error($exception->getTrace());
            return $this->asFailure("Sorry, we couldn't save your profile. Please try again.");
        }
        return $this->asSuccess('Profile saved');
    }
}