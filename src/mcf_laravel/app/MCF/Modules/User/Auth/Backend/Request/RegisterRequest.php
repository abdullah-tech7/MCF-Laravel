<?php

declare(strict_types=1);

namespace App\MCF\Modules\User\Auth\Backend\Request;

use App\MCF\Base\MfcRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

final class RegisterRequest extends MfcRequest
{
    protected function dataClass(): ?string
    {
        return RegisterData::class;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'phone' => [
                'nullable',
                'digits:10',
                Rule::unique('users', 'phone'),
            ],

            'password' => [
                'required',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
                __('Name is required.'),

            'name.max' =>
                __('Name may not be greater than 255 characters.'),

            'email.required' =>
                __('Email address is required.'),

            'email.email' =>
                __('Please enter a valid email address.'),

            'email.unique' =>
                __('This email address is already registered.'),

            'phone.digits' =>
                __('Phone number must contain exactly 10 digits.'),

            'phone.unique' =>
                __('This phone number is already registered.'),

            'password.required'              =>
            __('Password is required.'),

            'password.confirmed'             =>
            __('Password confirmation does not match.'),

            'password.min'                   =>
            __('Password must be at least 8 characters long and include uppercase and lowercase letters, a number, and a symbol.'),

            'password.letters'               =>
            __('Password must be at least 8 characters long and include uppercase and lowercase letters, a number, and a symbol.'),

            'password.mixed'                 =>
            __('Password must be at least 8 characters long and include uppercase and lowercase letters, a number, and a symbol.'),

            'password.numbers'               =>
            __('Password must be at least 8 characters long and include uppercase and lowercase letters, a number, and a symbol.'),

            'password.symbols'               =>
            __('Password must be at least 8 characters long and include uppercase and lowercase letters, a number, and a symbol.'),

        ];
    }
}

final readonly class RegisterData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone = null,
    ) {
    }
}
