<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (!Schema::hasColumn('articles', 'views')) {
                $table->unsignedBigInteger('views')->default(0)->after('content');
            }
            if (!Schema::hasColumn('articles', 'status')) {
                $table->string('status')->default('pending')->after('views');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['views', 'status']);
        });
    }
};
