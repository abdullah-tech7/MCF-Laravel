<?php


namespace App\MCF\Modules\User\Auth\Backend\Request;

use App\MCF\Base\MfcRequest;

final class VerifyRestoreAccountRequest extends MfcRequest
{
    protected function dataClass(): ?string
    {
        return VerifyRestoreAccountData::class;
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
            'code.required' => __(
                'Verification code is required.',
            ),
        ];
    }
}

final readonly class VerifyRestoreAccountData
{
    public function __construct(
        public string $code,
    ) {
    }
}
