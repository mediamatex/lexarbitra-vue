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
            $table->uuid('case_file_id');
            $table->uuid('user_id');
            $table->uuid('party_participant_id')->nullable();
            $table->uuid('case_party_id')->nullable();
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

            $table->index(['case_file_id', 'role']);
            $table->index(['case_file_id', 'user_id']);
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
