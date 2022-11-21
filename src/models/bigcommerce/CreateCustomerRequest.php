<?php
namespace venveo\bigcommerce\models\bigcommerce;

class CreateCustomerRequest extends BigCommerceRequest {
    public ?int $id;
    public ?string $email;
    public ?string $first_name;
    public ?string $last_name;
    public ?string $company;
    public ?string $password;
    public ?string $verifyPassword;

    public function rules(): array
    {
        return [
            [['email', 'first_name', 'last_name', 'password', 'verifyPassword'], 'required'],
        ];
    }
}