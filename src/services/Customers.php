<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\bigcommerce\services;

use craft\base\Component;
use venveo\bigcommerce\api\operations\customers\GetCurrentCustomer;

class Customers extends Component
{
    public function getCurrentCustomer()
    {
        return GetCurrentCustomer::getCurrentCustomer();
    }
}
