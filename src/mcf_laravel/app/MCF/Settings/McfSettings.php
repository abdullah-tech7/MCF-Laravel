<?php

declare (strict_types = 1);

namespace App\MCF\Settings;

use App\MCF\Authentication\UserSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class McfSettings
{
    private function __construct()
    {
    }

    /**
     * Resolve the authenticated user's role.
     *
     * Customize this method if the project
     * uses a different role structure.
     */
    public static function resolveRole(
        Authenticatable $user,
    ): int | string | null {
        return UserSettings::resolveRole($user);
    }

    /**
     * Get one setting value for a user.
     *
     * If the user has no override, the default value is returned.
     *
     * @throws InvalidArgumentException
     */
    public static function get(
        int $userId,
        string $key,
    ): mixed {
        $data = self::findData($key);

        $setting = DB::table('mcf_settings')
            ->where('user_id', $userId)
            ->where('data_id', $data->id)
            ->first();

        if ($setting === null) {
            return self::decode($data->default_value);
        }

        return self::decode($setting->value);
    }

    /**
     * Check whether a setting or one of its options is enabled.
     *
     * Boolean settings:
     *     isEnabled($userId, 'notifications_enabled')
     *
     * Multiselect settings:
     *     isEnabled($userId, 'ev_created', 'website')
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function isEnabled(
        int $userId,
        string $key,
        ?string $option = null,
    ): bool {
        $data  = self::findData($key);
        $value = self::get($userId, $key);

        if ($data->type === SettingType::BOOLEAN) {
            if ($option !== null) {
                throw new InvalidArgumentException(
                    "Option is not supported for boolean setting [{$key}]."
                );
            }

            return $value === true;
        }

        if ($data->type === SettingType::MULTISELECT) {
            if ($option === null) {
                throw new InvalidArgumentException(
                    "Option is required for multiselect setting [{$key}]."
                );
            }

            if (! is_array($value)) {
                throw new RuntimeException(
                    "Invalid value structure for multiselect setting [{$key}]."
                );
            }

            if (! array_key_exists($option, $value)) {
                throw new InvalidArgumentException(
                    "Option [{$option}] does not exist for setting [{$key}]."
                );
            }

            return $value[$option] === true;
        }

        throw new InvalidArgumentException(
            "Setting [{$key}] does not support isEnabled()."
        );
    }

    /**
     * Set a user's setting value.
     *
     * The setting must be visible to the user's role.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function set(
        int $userId,
        string $key,
        mixed $value,
    ): bool {
        $data = self::findData($key);

        self::validateUserCanAccessSetting(
            userId: $userId,
            data: $data,
        );

        self::validateValue(
            data: $data,
            value: $value,
        );

        DB::table('mcf_settings')->updateOrInsert(
            [
                'user_id' => $userId,
                'data_id' => $data->id,
            ],
            [
                'value'      => self::encode($value),
                'updated_at' => now(),
            ]
        );

        return true;
    }

    /**
     * Set one option of a multiselect setting.
     *
     * The setting must be visible to the user's role.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function setOption(
        int $userId,
        string $key,
        string $option,
        bool $value,
    ): bool {
        $data = self::findData($key);

        self::validateUserCanAccessSetting(
            userId: $userId,
            data: $data,
        );

        if ($data->type !== SettingType::MULTISELECT) {
            throw new InvalidArgumentException(
                "Setting [{$key}] is not a multiselect setting."
            );
        }

        $options = self::decodeOptions($data->options);

        if (! in_array($option, $options, true)) {
            throw new InvalidArgumentException(
                "Option [{$option}] does not exist for setting [{$key}]."
            );
        }

        $current = self::get(
            userId: $userId,
            key: $key,
        );

        if (! is_array($current)) {
            throw new RuntimeException(
                "Invalid value structure for multiselect setting [{$key}]."
            );
        }

        $current[$option] = $value;

        self::validateValue(
            data: $data,
            value: $current,
        );

        DB::table('mcf_settings')->updateOrInsert(
            [
                'user_id' => $userId,
                'data_id' => $data->id,
            ],
            [
                'value'      => self::encode($current),
                'updated_at' => now(),
            ]
        );

        return true;
    }

    public static function resetAll(
    int $userId,
): bool {
    DB::table('mcf_settings')
        ->where('user_id', $userId)
        ->delete();

    return true;
}

    /**
     * Reset a user's setting to its default value.
     *
     * The override row is removed so the default becomes effective.
     *
     * @throws InvalidArgumentException
     */
    public static function reset(
        int $userId,
        string $key,
    ): bool {
        $data = self::findData($key);

        self::validateUserCanAccessSetting(
            userId: $userId,
            data: $data,
        );

        DB::table('mcf_settings')
            ->where('user_id', $userId)
            ->where('data_id', $data->id)
            ->delete();

        return true;
    }

    /**
     * Get all settings visible to the user's role.
     *
     * The roles column is used only for visibility.
     * It is not returned to the API consumer.
     *
     * roles = null means visible to all roles.
     *
     * @throws InvalidArgumentException
     */
    public static function all(
        int $userId,
        ?string $category = null,
    ): array {
        $user = self::resolveUser($userId);

        $role = self::resolveRole($user);

        $query = DB::table('mcf_setting_data')
            ->orderBy('category')
            ->orderBy('id');

        if ($category !== null) {
            $query->where('category', $category);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return [];
        }

        $visibleData = $data
            ->filter(
                fn(object $item): bool => self::isVisibleForRole(
                    roles: self::decodeRoles($item->roles),
                    role: $role,
                )
            )
            ->values();

        if ($visibleData->isEmpty()) {
            return [];
        }

        $dataIds = $visibleData->pluck('id')->all();

        $settings = DB::table('mcf_settings')
            ->where('user_id', $userId)
            ->whereIn('data_id', $dataIds)
            ->get()
            ->keyBy('data_id');

        return $visibleData
            ->map(function (object $item) use ($settings): array {
                $userSetting = $settings->get($item->id);

                return [
                    'category'      => $item->category,
                    'key'           => $item->key,
                    'name'          => $item->name,
                    'subtitle'      => $item->subtitle,
                    'type'          => $item->type,
                    'options'       => self::decodeOptions($item->options),
                    'default_value' => self::decode($item->default_value),
                    'value'         => $userSetting === null
                        ? self::decode($item->default_value)
                        : self::decode($userSetting->value),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Find setting definition by key.
     *
     * @throws InvalidArgumentException
     */
    private static function findData(
        string $key,
    ): object {
        $data = DB::table('mcf_setting_data')
            ->where('key', $key)
            ->first();

        if ($data === null) {
            throw new InvalidArgumentException(
                "Setting [{$key}] does not exist."
            );
        }

        return $data;
    }

    /**
     * Resolve the user model from the configured notification/settings user model.
     *
     * @throws InvalidArgumentException
     */
    private static function resolveUser(
        int $userId,
    ): Authenticatable {
        $userModel = UserSettings::model();

        if (! class_exists($userModel)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Settings user model does not exist: %s.',
                    $userModel,
                ),
            );
        }

        $user = $userModel::query()
            ->find($userId);

        if (! $user instanceof Authenticatable) {
            throw new InvalidArgumentException(
                sprintf(
                    'Settings user does not exist: %s.',
                    $userId,
                ),
            );
        }

        return $user;
    }

    /**
     * Validate that the setting is visible to the user's role.
     *
     * This is visibility/availability logic, not MCF Access permission logic.
     *
     * @throws InvalidArgumentException
     */
    private static function validateUserCanAccessSetting(
        int $userId,
        object $data,
    ): void {
        $user = self::resolveUser($userId);

        $role = self::resolveRole($user);

        $roles = self::decodeRoles($data->roles);

        if (! self::isVisibleForRole(
            roles: $roles,
            role: $role,
        )) {
            throw new InvalidArgumentException(
                "Setting [{$data->key}] is not available for the user's role."
            );
        }
    }

    /**
     * Determine whether a setting is visible for a role.
     *
     * roles = null means visible to all roles.
     */
    private static function isVisibleForRole(
        ?array $roles,
        int | string | null $role,
    ): bool {
        if ($role === null) {
            return true;
        }

        if ($roles === null) {
            return true;
        }

        return in_array(
            $role,
            $roles,
            true,
        );
    }
    /**
     * Validate a setting value according to its definition.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private static function validateValue(
        object $data,
        mixed $value,
    ): void {
        switch ($data->type) {
            case SettingType::BOOLEAN:
                if (! is_bool($value)) {
                    throw new InvalidArgumentException(
                        "Setting [{$data->key}] expects a boolean value."
                    );
                }

                return;

            case SettingType::SELECT:
            case SettingType::RADIO:
                if (! is_string($value)) {
                    throw new InvalidArgumentException(
                        "Setting [{$data->key}] expects a string value."
                    );
                }

                $options = self::decodeOptions($data->options);

                if (! in_array($value, $options, true)) {
                    throw new InvalidArgumentException(
                        "Value [{$value}] does not exist in setting [{$data->key}] options."
                    );
                }

                return;

            case SettingType::MULTISELECT:
                if (! is_array($value)) {
                    throw new InvalidArgumentException(
                        "Setting [{$data->key}] expects an array value."
                    );
                }

                $options = self::decodeOptions($data->options);

                foreach ($options as $option) {
                    if (! array_key_exists($option, $value)) {
                        throw new InvalidArgumentException(
                            "Option [{$option}] is missing from setting [{$data->key}] value."
                        );
                    }

                    if (! is_bool($value[$option])) {
                        throw new InvalidArgumentException(
                            "Option [{$option}] in setting [{$data->key}] must be boolean."
                        );
                    }
                }

                foreach (array_keys($value) as $option) {
                    if (! in_array($option, $options, true)) {
                        throw new InvalidArgumentException(
                            "Option [{$option}] does not exist in setting [{$data->key}]."
                        );
                    }
                }

                return;

            case SettingType::TEXT:
            case SettingType::TEXTAREA:
                if (! is_string($value)) {
                    throw new InvalidArgumentException(
                        "Setting [{$data->key}] expects a string value."
                    );
                }

                return;

            case SettingType::NUMBER:
                if (! is_int($value) && ! is_float($value)) {
                    throw new InvalidArgumentException(
                        "Setting [{$data->key}] expects a numeric value."
                    );
                }

                return;

            default:
                throw new InvalidArgumentException(
                    "Unsupported setting type [{$data->type}]."
                );
        }
    }

    /**
     * Decode setting role visibility.
     */
    private static function decodeRoles(
        mixed $roles,
    ): ?array {
        if ($roles === null) {
            return null;
        }

        if (is_array($roles)) {
            return $roles;
        }

        $decoded = json_decode(
            $roles,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        if (! is_array($decoded)) {
            throw new RuntimeException(
                'Setting roles must be a JSON array.'
            );
        }

        return $decoded;
    }

    /**
     * Decode setting options.
     */
    private static function decodeOptions(
        mixed $options,
    ): ?array {
        if ($options === null) {
            return null;
        }

        if (is_array($options)) {
            return $options;
        }

        $decoded = json_decode(
            $options,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        if (! is_array($decoded)) {
            throw new RuntimeException(
                'Setting options must be a JSON array.'
            );
        }

        return $decoded;
    }

    /**
     * Decode a JSON setting value.
     */
    private static function decode(
        mixed $value,
    ): mixed {
        if (! is_string($value)) {
            return $value;
        }

        return json_decode(
            $value,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * Encode a setting value as JSON.
     */
    private static function encode(
        mixed $value,
    ): string {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
        );
    }
}
