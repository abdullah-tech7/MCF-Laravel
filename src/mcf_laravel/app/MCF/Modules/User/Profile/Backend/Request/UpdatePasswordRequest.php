<?php

declare (strict_types = 1);

namespace App\MCF\Modules\User\Profile\Backend\Request;

use App\MCF\Base\MfcRequest;
use Illuminate\Validation\Rules\Password;

final class UpdatePasswordRequest extends MfcRequest
{
    protected function dataClass(): ?string
    {
        return UpdatePasswordData::class;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
            ],
            'password'         => [
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
            'current_password.required' =>
            __('Current password is required.'),

            'password.required'         =>
            __('New password is required.'),

            'password.confirmed'        =>
            __('Password confirmation does not match.'),
            'password.min'              =>
            __('Password must be at least 8 characters long and include uppercase and lowercase letters, a number, and a symbol.'),

            'password.letters'          =>
            __('Password must be at least 8 characters long and include uppercase and lowercase letters, a number, and a symbol.'),

            'password.mixed'            =>
            __('Password must be at least 8 characters long and include uppercase and lowercase letters, a number, and a symbol.'),

            'password.numbers'          =>
            __('Password must be at least 8 characters long and include uppercase and lowercase letters, a number, and a symbol.'),

            'password.symbols'          =>
            __('Password must be at least 8 characters long and include uppercase and lowercase letters, a number, and a symbol.'),
        ];
    }
}

final readonly class UpdatePasswordData
{
    public function __construct(
        public string $current_password,
        public string $password,
    ) {
    }
}
