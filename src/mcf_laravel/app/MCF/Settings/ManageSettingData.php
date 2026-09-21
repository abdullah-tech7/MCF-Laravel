<?php

declare (strict_types = 1);

namespace App\MCF\Settings;

use Illuminate\Support\Facades\DB;

final class ManageSettingData
{
    public static function data(): array
    {
        return [


        ];
    }

    public static function sync(): void
    {
        DB::transaction(function (): void {
            foreach (self::data() as $setting) {
                DB::table('mcf_setting_data')->updateOrInsert(
                    [
                        'category' => $setting->category,
                        'key'      => $setting->key,
                    ],
                    [
                        'name'          => $setting->name,
                        'subtitle'      => $setting->subtitle,
                        'type'          => $setting->type,
                        'options'       => $setting->options === null
                            ? null
                            : json_encode(
                            $setting->options,
                            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                        ),
                        'default_value' => json_encode(
                            $setting->defaultValue,
                            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                        ),
                        'roles'         => $setting->roles === null
                            ? null
                            : json_encode(
                            $setting->roles,
                            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                        ),
                        'updated_at'    => now(),
                    ]
                );
            }
        });
    }
}
