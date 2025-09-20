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
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('case_file_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->string('file_hash', 64); // SHA-256 hash for integrity
            $table->enum('category', [
                'initiation',
                'pleading',
                'evidence',
                'decision',
                'communication',
                'protocol',
                'correspondence',
                'expert_report',
            ]);
            $table
                ->enum('visibility', [
                    'public', // All parties can see
                    'referee_only', // Only referees
                    'parties_only', // All parties but not experts
                    'restricted', // Custom access
                ])
                ->default('public');
            $table->uuid('uploaded_by');
            $table->boolean('is_signed')->default(false);
            $table->json('signature_info')->nullable(); // Digital signature details
            $table->integer('version')->default(1);
            $table->uuid('parent_document_id')->nullable(); // For versioning
            $table->boolean('is_current_version')->default(true);
            $table->text('version_notes')->nullable();
            $table->timestamps();

            $table->index(['case_file_id', 'category']);
            $table->index(['case_file_id', 'visibility']);
            $table->index(['uploaded_by', 'created_at']);
            $table->index('file_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
