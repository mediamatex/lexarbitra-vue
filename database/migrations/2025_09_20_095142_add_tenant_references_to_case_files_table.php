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
        Schema::table('case_files', function (Blueprint $table) {
            $table->uuid('tenant_case_id')->nullable()->after('id')
                ->comment('ID of the case record in the tenant database');
            $table->foreignId('database_connection_id')->nullable()->after('tenant_case_id')
                ->constrained('case_database_connections')->onDelete('set null')
                ->comment('Reference to the tenant database connection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('case_files', function (Blueprint $table) {
            $table->dropForeign(['database_connection_id']);
            $table->dropColumn(['tenant_case_id', 'database_connection_id']);
        });
    }
};
