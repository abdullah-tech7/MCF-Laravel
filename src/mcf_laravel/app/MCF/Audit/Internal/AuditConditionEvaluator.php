<?php

declare(strict_types=1);

namespace App\MCF\Audit\Internal;

use Closure;
use Illuminate\Database\Eloquent\Model;

final class AuditConditionEvaluator
{
    /**
     * Determine whether the model satisfies
     * the configured audit condition.
     *
     * Supported conditions:
     *
     * - null
     * - array<string, mixed>
     * - Closure(Model): bool
     *
     * Array example:
     *
     * condition: [
     *     'role_id' => 5,
     * ],
     *
     * Closure example:
     *
     * condition: fn (Model $model): bool =>
     *     $model->created_by === McfAuth::id(),
     *
     * @param array<string, mixed>|Closure(Model): bool|null $condition
     */
    public function passes(
        Model $model,
        array|Closure|null $condition,
    ): bool {
        if (
            $condition === null
            || $condition === []
        ) {
            return true;
        }

        /*
         * Dynamic condition.
         */
        if ($condition instanceof Closure) {
            return (bool) $condition($model);
        }

        /*
         * Static row-value conditions.
         */
        foreach ($condition as $column => $expectedValue) {
            if (
                $model->getAttribute($column) !== $expectedValue
            ) {
                return false;
            }
        }

        return true;
    }
}
