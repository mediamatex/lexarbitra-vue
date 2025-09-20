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
        Schema::create('case_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('case_number')->unique(); // Az. 1/2024
            $table->string('title');
            $table
                ->enum('status', [
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
                ])
                ->default('draft');
            $table->date('initiated_at');
            $table->uuid('created_by')->nullable()->comment('User who created this case file');
            $table->timestamps();

            // Basic indexes for landlord database operations
            $table->index(['status', 'initiated_at']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_files');
    }
};
