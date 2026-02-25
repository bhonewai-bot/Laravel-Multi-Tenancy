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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();               // Customer, Product
            $table->string('slug')->unique();               // customer, product
            $table->string('version')->default('1.0.0');    
            $table->string('description')->nullable();
            $table->string('icon_path')->nullable();
            $table->decimal('price', 10, 2)->default(0);    
            $table->boolean('is_active')->default(true);       // available in marketplace
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
