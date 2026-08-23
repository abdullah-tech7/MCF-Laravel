<?php


namespace App\MCF\Base;

use Illuminate\Foundation\Http\FormRequest;
use LogicException;

abstract class MfcRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Data Contract
    |--------------------------------------------------------------------------
    */

    /**
     * Return the Data class associated with this request.
     *
     * Override this method when the request has
     * a dedicated Data object.
     */
    protected function dataClass(): ?string
    {
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Data
    |--------------------------------------------------------------------------
    */

    /**
     * Return the validated request data.
     *
     * If a Data class is defined, the validated input
     * is converted into that Data object.
     *
     * Requests without a Data class receive the
     * validated array directly.
     */
    public function getData(): object|array
    {
        $validated = $this->validated();

        $dataClass = $this->dataClass();

        if ($dataClass === null) {
            return $validated;
        }

        if (! class_exists($dataClass)) {
            throw new LogicException(
                sprintf(
                    '%s::dataClass() returned an invalid class: %s.',
                    static::class,
                    $dataClass,
                ),
            );
        }

        try {
            return new $dataClass(
                ...$validated,
            );
        } catch (\Throwable $exception) {
            throw new LogicException(
                sprintf(
                    'Unable to create Data object %s from %s.',
                    $dataClass,
                    static::class,
                ),
                previous: $exception,
            );
        }
    }
}
