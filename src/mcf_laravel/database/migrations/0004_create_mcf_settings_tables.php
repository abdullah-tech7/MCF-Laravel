<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcf_setting_data', function (Blueprint $table) {
            $table->id();

            $table->string('category', 100);
            $table->string('key', 100);
            $table->string('name', 255);
            $table->string('subtitle', 500)->nullable();
            $table->string('type', 50);
            $table->json('options')->nullable();
            $table->json('default_value');
            $table->json('roles')->nullable();

            $table->timestamps();

            $table->unique(
                ['category', 'key'],
                'mcf_setting_data_category_key_unique'
            );

            $table->index(
                'category',
                'mcf_setting_data_category_index'
            );

            $table->index(
                'key',
                'mcf_setting_data_key_index'
            );

            $table->index(
                'type',
                'mcf_setting_data_type_index'
            );
        });

        Schema::create('mcf_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('data_id')
                ->constrained('mcf_setting_data')
                ->cascadeOnDelete();

            $table->json('value');

            $table->timestamps();

            $table->unique(
                ['user_id', 'data_id'],
                'mcf_settings_user_data_unique'
            );

            $table->index(
                'user_id',
                'mcf_settings_user_id_index'
            );

            $table->index(
                'data_id',
                'mcf_settings_data_id_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcf_settings');
        Schema::dropIfExists('mcf_setting_data');
    }
};