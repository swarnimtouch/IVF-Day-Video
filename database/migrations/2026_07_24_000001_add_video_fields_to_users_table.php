<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->index();
            $table->string('employee_code', 30)->nullable()->index();
            $table->string('prefix', 10)->nullable();
            $table->string('city', 80)->nullable();
            $table->text('photo_url')->nullable();
            $table->string('photo_key')->nullable();
            $table->text('video_url')->nullable();
            $table->string('video_key')->nullable();
            $table->string('download_token', 64)->nullable()->unique();
            $table->string('video_status', 20)->default('pending')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_admin', 'employee_code', 'prefix', 'city', 'photo_url', 'photo_key',
                'video_url', 'video_key', 'download_token', 'video_status',
            ]);
        });
    }
};
