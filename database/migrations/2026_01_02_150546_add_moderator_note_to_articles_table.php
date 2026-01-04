<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('articles', 'moderator_note')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->text('moderator_note')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('articles', 'moderator_note')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('moderator_note');
            });
        }
    }
};
