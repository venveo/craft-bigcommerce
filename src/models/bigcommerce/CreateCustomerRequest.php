<?php
namespace venveo\bigcommerce\models\bigcommerce;

use venveo\bigcommerce\validators\CustomerExistsValidator;

class CreateCustomerRequest extends BigCommerceRequest {
    public ?int $id = null;
    public ?string $email = null;
    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $company = null;
    public ?string $password = null;
    public ?int $customer_group_id = null;
    public ?array $channel_ids = null;
    public ?string $verifyPassword = null;

    public const SCENARIO_VERIFY_ACCOUNT = 'SCENARIO_VERIFY_ACCOUNT';

    public function createPayload(): array|string
    {
        return array_filter([
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'company' => $this->company,
            'authentication' => [
                'force_password_reset' => false,
                'new_password' => $this->password
            ],
            'channel_ids' => $this->channel_ids,
            'customer_group_id' => $this->customer_group_id
        ]);
    }

    public function rules(): array
    {
        return [
            [['email', 'first_name', 'last_name', 'password', 'verifyPassword'], 'required'],
            [['company'], 'safe'],
            ['password', 'compare', 'compareAttribute' => 'verifyPassword'],
            ['email', CustomerExistsValidator::class, 'negate' => true, 'on' => self::SCENARIO_VERIFY_ACCOUNT]
        ];
    }
}