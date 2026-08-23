<?php


namespace App\MCF\Modules\User\Auth\Backend\Request;

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
                'string',
                'confirmed',
            ],

            'password_confirmation' => [
                'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' =>
                __('Password is required.'),

            'password.confirmed' =>
                __('The password confirmation does not match.'),

            'password_confirmation.required' =>
                __('Password confirmation is required.'),
        ];
    }
}

final readonly class ResetPasswordData
{
    public function __construct(
        public string $password,
        public string $password_confirmation,
    ) {
    }
}
