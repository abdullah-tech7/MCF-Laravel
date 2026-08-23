<?php


namespace App\MCF\Modules\User\Profile\Backend\Request;

use App\MCF\Base\MfcRequest;

final class VerifyUpdateEmailRequest extends MfcRequest
{
    protected function dataClass(): ?string
    {
        return VerifyUpdateEmailData::class;
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

final readonly class VerifyUpdateEmailData
{
    public function __construct(
        public string $code,
    ) {
    }
}
