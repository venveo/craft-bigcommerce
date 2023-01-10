<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\services;

use craft\base\Component;
use venveo\bigcommerce\api\operations\Customer;

class Customers extends Component
{
    /**
     * @return int|null
     */
    public function getCurrentCustomerId(): ?int
    {
        return Customer::getCurrentCustomerId();
    }

    /**
     * @return \BigCommerce\ApiV3\ResourceModels\Customer\Customer|null
     */
    public function getCurrentCustomer()
    {

        return Customer::getCurrentCustomer();
    }
}
