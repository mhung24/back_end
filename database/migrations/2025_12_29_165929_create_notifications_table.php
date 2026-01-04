<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('recipient_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('actor_id')->constrained('users')->onDelete('cascade');

            $table->string('type'); // Đã sửa từ enum thành string để linh hoạt

            $table->uuid('entity_id')->nullable(); // Để nullable cho an toàn

            $table->string('title')->nullable(); // Cột mới
            $table->text('message')->nullable(); // Cột mới

            $table->boolean('is_read')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
