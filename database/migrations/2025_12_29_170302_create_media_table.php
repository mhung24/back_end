<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('uploader_id')->constrained('users')->onDelete('cascade');

            $table->text('url');
            // -----------------------------

            $table->string('file_type', 50);
            $table->string('alt_text', 255)->nullable();
            $table->integer('file_size')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
