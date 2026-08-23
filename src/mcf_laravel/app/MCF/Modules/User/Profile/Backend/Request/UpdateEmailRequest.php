<?php


namespace App\MCF\Modules\User\Profile\Backend\Request;

use App\MCF\Base\MfcRequest;

final class UpdateEmailRequest extends MfcRequest
{
    protected function dataClass(): ?string
    {
        return UpdateEmailData::class;
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
                'max:255',
                'unique:users,email',
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

            'email.max' =>
                __('Email address may not be greater than 255 characters.'),

            'email.unique' =>
                __('This email address is already in use.'),
        ];
    }
}

final readonly class UpdateEmailData
{
    public function __construct(
        public string $email,
    ) {
    }
}
