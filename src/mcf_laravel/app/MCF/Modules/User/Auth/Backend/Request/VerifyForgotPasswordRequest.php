<?php


namespace App\MCF\Modules\User\Auth\Backend\Request;

use App\MCF\Base\MfcRequest;

final class VerifyForgotPasswordRequest extends MfcRequest
{
    protected function dataClass(): ?string
    {
        return VerifyForgotPasswordData::class;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' =>
                __('Verification code is required.'),
        ];
    }
}

final readonly class VerifyForgotPasswordData
{
    public function __construct(
        public string $code,
    ) {
    }
}
