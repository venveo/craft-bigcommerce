<?php
namespace venveo\bigcommerce\models\bigcommerce;

use craft\base\Model;

abstract class BigCommerceRequest extends Model {
    abstract public function createPayload(): array|string;
}