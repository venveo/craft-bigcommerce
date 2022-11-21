<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\controllers;

use craft\web\Controller;
use venveo\bigcommerce\Plugin;

class CustomersController extends Controller
{
    public $enableCsrfValidation = true;
    public array|bool|int $allowAnonymous = ['register'];

    public function actionRegister()
    {
        $this->requirePostRequest();
        $client = Plugin::getInstance()->getApi()->getClient();
        try {
            $payload = [
                'email' => $this->request->getRequiredBodyParam('email'),
                'first_name' => $this->request->getRequiredBodyParam('first_name'),
                'last_name' => $this->request->getRequiredBodyParam('last_name'),
                'company' => $this->request->getBodyParam('company'),
                'authentication' => [
                    'force_password_reset' => false,
                    'new_password' => $this->request->getRequiredBodyParam('password')
                ],
                'channel_ids' => [1],
                'customer_group_id' => 2 // Website Signups
            ];
        } catch(\Exception $exception) {
            return $this->asFailure('Missing required field.');
        }

        $existingCustomer = null;
        try {
            $existingCustomer = $client->customers()->getByEmail($payload['email']) ?? null;
        } catch (\Exception $e) {
        }

        if ($existingCustomer) {
            return $this->asFailure('User already exists');
        }

        try {
            $customer = $client->customers()->create([$payload])->getCustomers()[0];
        } catch (\Exception $e) {
            return $this->asFailure('Failed to create customer');
        }

    }
}
