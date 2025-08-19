<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->string('username', 100)->unique();

            // Email - NULLABLE برای کاربران فقط موبایل
            $table->string('email', 255)->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();

            // Password - NULLABLE برای Google OAuth users
            $table->string('password', 255)->nullable();

            // Phone - NULLABLE برای کاربران فقط email
            $table->string('phone', 20)->nullable()->unique();
            $table->string('country_code', 5)->nullable();
            $table->timestamp('phone_verified_at')->nullable();

            // Location fields
            $table->unsignedInteger('province_id')->nullable();
            $table->unsignedInteger('city_id')->nullable();

            $table->rememberToken();

            // Two Factor Authentication fields
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            // User Status
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Google OAuth fields
            $table->string('google_id')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->timestamp('google_token_expires_at')->nullable();
            $table->enum('registration_type', ['normal', 'google'])->default('normal');

            // Indexes برای performance
            $table->index('email');
            $table->index('phone');
            $table->index('google_id');
            $table->index('username');
            $table->index('registration_type');
            $table->index(['province_id', 'city_id']);
            $table->index('is_active');
            $table->index('created_at');
            $table->index('last_login_at');
            $table->index(['email_verified_at', 'phone_verified_at']);
            $table->index('is_admin');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
