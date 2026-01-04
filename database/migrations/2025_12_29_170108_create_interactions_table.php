<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('article_id')->constrained('articles')->onDelete('cascade');

            $table->enum('type', ['like', 'bookmark']);


            $table->unique(['user_id', 'article_id', 'type']);

            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
