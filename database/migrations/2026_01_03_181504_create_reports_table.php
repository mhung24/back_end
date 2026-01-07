<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            // Sửa dòng này từ $table->id() thành:
            $table->uuid('id')->primary();

            $table->foreignUuid('reporter_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('reported_user_id')->constrained('users')->onDelete('cascade');

            $table->string('type');
            $table->enum('priority', ['Low', 'Medium', 'High', 'Urgent'])->default('Medium');
            $table->text('description');
            $table->json('evidence')->nullable();

            $table->enum('status', ['Pending', 'In Progress', 'Resolved', 'Dismissed'])->default('Pending');
            $table->text('moderator_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
