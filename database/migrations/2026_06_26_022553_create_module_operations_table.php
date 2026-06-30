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
        Schema::create('module_operations', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('module_slug');
            $table->string('action');
            $table->string('status');
            $table->string('message')->default('');
            $table->timestamps();

            $table->unique(['tenant_id', 'module_slug']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_operations');
    }
};
