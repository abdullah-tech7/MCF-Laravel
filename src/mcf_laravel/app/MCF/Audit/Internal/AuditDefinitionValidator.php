<?php

declare(strict_types=1);

namespace App\MCF\Audit\Internal;

use App\MCF\Audit\Data\AuditDefinition;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use LogicException;

final class AuditDefinitionValidator
{
    /**
     * Validate an Audit Definition for the given model.
     *
     * @throws LogicException
     */
    public function validate(
        Model $model,
        AuditDefinition $definition,
    ): void {
        $this->validateAction(
            $definition->action,
        );

        $table = $model->getTable();

        $columns = Schema::getColumnListing(
            $table,
        );

        $this->validateColumns(
            $definition->columns,
            $columns,
            $model,
            'columns',
        );

        $this->validateCondition(
            $definition->condition,
            $columns,
            $model,
        );
    }

    /**
     * Validate the configured Audit action.
     *
     * @throws LogicException
     */
    private function validateAction(
        string $action,
    ): void {
        if (
            ! in_array(
                strtolower(trim($action)),
                [
                    'create',
                    'update',
                    'delete',
                ],
                true,
            )
        ) {
            throw new LogicException(
                sprintf(
                    'Invalid audit action [%s]. Allowed actions are: create, update, delete.',
                    $action,
                ),
            );
        }
    }

    /**
     * Validate configured columns.
     *
     * @param string[] $configuredColumns
     * @param string[] $databaseColumns
     *
     * @throws LogicException
     */
    private function validateColumns(
        array $configuredColumns,
        array $databaseColumns,
        Model $model,
        string $source,
    ): void {
        foreach ($configuredColumns as $column) {

            if ($column === 'any') {
                continue;
            }

            if (
                ! in_array(
                    $column,
                    $databaseColumns,
                    true,
                )
            ) {
                throw new LogicException(
                    sprintf(
                        'Audit %s column [%s] does not exist in table [%s] for model [%s].',
                        $source,
                        $column,
                        $model->getTable(),
                        $model::class,
                    ),
                );
            }
        }
    }

    /**
     * Validate the configured condition.
     *
     * Array conditions are validated against
     * the model table columns.
     *
     * Closure conditions are dynamic and therefore
     * do not require column validation.
     *
     * @param array<string, mixed>|Closure(Model): bool|null $condition
     * @param string[] $databaseColumns
     *
     * @throws LogicException
     */
    private function validateCondition(
        array|Closure|null $condition,
        array $databaseColumns,
        Model $model,
    ): void {
        if (
            $condition === null
            || $condition === []
        ) {
            return;
        }

        if ($condition instanceof Closure) {
            return;
        }

        $this->validateColumns(
            array_keys($condition),
            $databaseColumns,
            $model,
            'condition',
        );
    }
}
