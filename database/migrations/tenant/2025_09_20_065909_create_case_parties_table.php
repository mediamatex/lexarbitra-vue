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
        Schema::create('case_parties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('case_file_id');
            $table->string('company_name'); // Firma A, Firma B
            $table->string('party_type'); // 'claimant', 'respondent'
            $table->text('company_address')->nullable();
            $table->string('company_registration_number')->nullable();
            $table->string('company_tax_id')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['case_file_id', 'party_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_parties');
    }
};