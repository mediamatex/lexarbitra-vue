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
        Schema::create('case_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('case_reference_id'); // Links to case_references table
            $table->uuid('user_id');
            $table->enum('role', [
                'chairman',
                'referee',
                'co_referee',
                'claimant',
                'respondent',
                'expert',
                'witness',
                'administrator',
                'lawyer',
                'assistant',
            ]);
            $table->boolean('is_primary')->default(false); // Main representative
            $table->date('appointed_at')->nullable();
            $table->date('removed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('case_reference_id')
                  ->references('id')
                  ->on('case_references')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Ensure unique role per user per case
            $table->unique(['case_reference_id', 'user_id', 'role'], 'unique_case_user_role');

            // Index for performance
            $table->index(['case_reference_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_participants');
    }
};
