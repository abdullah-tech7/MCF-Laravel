<?php


namespace App\MCF\Modules\User\Profile\Backend\Request;

use App\MCF\Base\MfcRequest;

final class VerifyDeleteAccountRequest extends MfcRequest
{
    protected function dataClass(): ?string
    {
        return VerifyDeleteAccountData::class;
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

final readonly class VerifyDeleteAccountData
{
    public function __construct(
        public string $code,
    ) {
    }
}
