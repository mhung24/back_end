<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            $table->string('phone', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();

            $table->enum('role', ['reader', 'author', 'moderator', 'admin', 'banned'])->default('reader');

            $table->integer('years_of_experience')->default(0);
            $table->text('bio')->nullable();
            $table->integer('reputation_score')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
