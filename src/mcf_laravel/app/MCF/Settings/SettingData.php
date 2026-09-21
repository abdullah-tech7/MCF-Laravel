<?php
namespace App\MCF\Settings;

final class SettingData
{
    public function __construct(
        public readonly string $category,
        public readonly string $key,
        public readonly string $name,
        public readonly ?string $subtitle,
        public readonly string $type,
        public readonly ?array $options,
        public readonly mixed $defaultValue,
        public readonly ?array $roles,
    ) {}
}
