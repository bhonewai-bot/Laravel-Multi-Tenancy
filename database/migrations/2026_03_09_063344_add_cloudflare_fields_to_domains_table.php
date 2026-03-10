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
        Schema::table('domains', function (Blueprint $table) {
            $table->string('cf_hostname_id')->nullable()->unique()->after('verified_at');
            $table->string('cf_hostname_status')->nullable()->after('cf_hostname_id');
            $table->index('cf_hostname_status');
            $table->string('cf_ssl_status')->nullable()->after('cf_hostname_status');
            $table->index('cf_ssl_status');
            $table->timestamp('cf_last_checked_at')->nullable()->after('cf_ssl_status');
            $table->index('cf_last_checked_at');
            $table->text('cf_error')->nullable()->after('cf_last_checked_at');
            $table->json('cf_payload')->nullable()->after('cf_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('cf_payload');
            $table->dropColumn('cf_error');
            $table->dropIndex(['cf_last_checked_at']);
            $table->dropColumn('cf_last_checked_at');
            $table->dropIndex(['cf_ssl_status']);
            $table->dropColumn('cf_ssl_status');
            $table->dropIndex(['cf_hostname_status']);
            $table->dropColumn('cf_hostname_status');
            $table->dropUnique(['cf_hostname_id']);
            $table->dropColumn('cf_hostname_id');
        });
    }
};
