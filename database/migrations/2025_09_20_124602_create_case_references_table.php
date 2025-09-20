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
        Schema::create('case_references', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Tenant database reference
            $table->uuid('tenant_case_id')->nullable()->comment('ID of the case in the tenant database');

            // Database connection info
            $table->string('database_name');
            $table->string('database_user');
            $table->text('database_password')->nullable(); // Encrypted
            $table->string('database_host');
            $table->string('connection_name')->unique();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('connection_name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_references');
    }
};
