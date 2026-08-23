<?php


namespace App\MCF\Modules\User\Auth\Backend\Request;

use App\MCF\Base\MfcRequest;

final class LoginRequest extends MfcRequest
{
    protected function dataClass(): ?string
    {
        return LoginData::class;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],

            'remember' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' =>
                __('Email address is required.'),

            'email.email' =>
                __('Please enter a valid email address.'),

            'password.required' =>
                __('Password is required.'),
        ];
    }
}

final readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember=false,
    ) {
    }
}
