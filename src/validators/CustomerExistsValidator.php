<?php
namespace venveo\bigcommerce\validators;

use Craft;
use venveo\bigcommerce\Plugin;
use yii\validators\Validator;

class CustomerExistsValidator extends Validator {
    public bool $negate = false;

    public function validateAttribute($model, $attribute)
    {
        $emailAddress = $model->$attribute;
        $exists = false;
        try {
            $customer = Plugin::getInstance()->getApi()->getClient()->customers()->getByEmail($emailAddress);
            if ($customer) {
                $exists = true;
            }
        } catch (\Exception $e) {
            //
        }

        if ($exists && $this->negate) {
            $model->addError($attribute, Craft::t('bigcommerce', 'A customer already exists for that email address.'));
        }
    }
}