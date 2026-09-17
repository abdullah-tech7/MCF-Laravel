<?php

declare(strict_types=1);

namespace App\MCF\Audit\Data;

use App\MCF\Notification\Internal\NotificationRequest;
use Closure;
use Illuminate\Database\Eloquent\Model;

final readonly class AuditDefinition
{
    /**
     * @param string[] $columns
     * @param array<string, mixed>|Closure(Model): bool|null $condition
     * @param Closure(Model): NotificationRequest|null $notification
     */
    public function __construct(
        public string $action,
        public array $columns,
        public array|Closure|null $condition,
        public string $message,
        public ?Closure $notification = null,
    ) {
    }
}
