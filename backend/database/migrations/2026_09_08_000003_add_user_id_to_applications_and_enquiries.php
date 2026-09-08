<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive-only: nullable so every existing guest submission remains
     * valid and untouched. Only new, authenticated submissions populate it.
     */
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('admission_applications', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('enquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('enquiries', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            if (Schema::hasColumn('admission_applications', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });

        Schema::table('enquiries', function (Blueprint $table) {
            if (Schema::hasColumn('enquiries', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
