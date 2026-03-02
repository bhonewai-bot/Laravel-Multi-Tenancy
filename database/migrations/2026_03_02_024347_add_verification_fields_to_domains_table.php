<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('domains', 'verification_code') || !Schema::hasColumn('domains', 'verified_at')) {
            Schema::table('domains', function (Blueprint $table) {
                if (!Schema::hasColumn('domains', 'verification_code')) {
                    $table->string('verification_code', 64)->nullable()->after('tenant_id');
                }

                if (!Schema::hasColumn('domains', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('verification_code');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('domains', 'verified_at') || Schema::hasColumn('domains', 'verification_code')) {
            Schema::table('domains', function (Blueprint $table) {
                if (Schema::hasColumn('domains', 'verified_at')) {
                    $table->dropColumn('verified_at');
                }

                if (Schema::hasColumn('domains', 'verification_code')) {
                    $table->dropColumn('verification_code');
                }
            });
        }
    }
};
