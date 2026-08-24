<?php

declare(strict_types=1);

namespace App\MCF\Modules\User\Auth\Backend\Request;
use Illuminate\Validation\Rules\Password;

use App\MCF\Base\MfcRequest;

final class ResetPasswordRequest extends MfcRequest
{
    protected function dataClass(): ?string
    {
        return ResetPasswordData::class;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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

final readonly class ResetPasswordData
{
    public function __construct(
        public string $password,
    ) {
    }
}
