<?php


namespace App\MCF\Modules\User\Auth\Backend\Request;

use App\MCF\Base\MfcRequest;

final class ForgotPasswordRequest extends MfcRequest
{
    protected function dataClass(): ?string
    {
        return ForgotPasswordData::class;
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
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' =>
                __('Email address is required.'),

            'email.email' =>
                __('Please enter a valid email address.'),
        ];
    }
}

final readonly class ForgotPasswordData
{
    public function __construct(
        public string $email,
    ) {
    }
}
