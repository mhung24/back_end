<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->foreignUuid('follower_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('author_id')->constrained('users')->onDelete('cascade');

            $table->primary(['follower_id', 'author_id']);

            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
