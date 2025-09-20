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
        Schema::create('case_database_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('case_file_id');
            $table->string('database_name');
            $table->string('database_user');
            $table->text('database_password'); // Encrypted
            $table->string('database_host');
            $table->string('connection_name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['case_file_id', 'is_active']);
            $table->index('database_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_database_connections');
    }
};
