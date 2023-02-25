<?php

namespace venveo\bigcommerce\base;

use craft\web\Controller;
use venveo\bigcommerce\api\operations\Customer;
use yii\web\ForbiddenHttpException;

abstract class BigCommerceApiController extends Controller
{
    public function requireCustomer()
    {
        $customer = Customer::getCurrentCustomerId();
        if (!$customer) {
            throw new ForbiddenHttpException('You must be logged in to perform that action');
        }
    }
}