<?php

use App\Services\TenantModuleRegistry;
use Illuminate\Database\Migrations\Migration;

/**
 * Migrates module state from tenant data JSON blobs into dedicated tables.
 *
 * Reads installed_modules and module_operations from the tenant data column
 * and inserts them into the new module_installations and module_operations tables.
 * The JSON blob data is preserved for rollback safety.
 */
return new class extends Migration
{
    public function up(): void
    {
        TenantModuleRegistry::migrateFromJsonBlobs();
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('module_operations')->delete();
        \Illuminate\Support\Facades\DB::table('module_installations')->delete();
    }
};
