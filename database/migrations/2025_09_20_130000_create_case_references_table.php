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

            // Basic case reference info
            $table->string('case_number')->unique(); // Az. 1/2024
            $table->string('title');
            $table->enum('status', [
                'draft',
                'active',
                'initiated',
                'pending',
                'hearing_scheduled',
                'under_deliberation',
                'suspended',
                'settled',
                'decided',
                'closed',
            ])->default('draft');
            $table->date('initiated_at');
            $table->uuid('created_by')->nullable()->comment('User who created this case file');

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

            // Indexes for landlord database operations
            $table->index(['status', 'initiated_at']);
            $table->index('created_by');
            $table->index('case_number');
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