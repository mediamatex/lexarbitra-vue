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
            $table->text('description')->nullable();
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
            $table
                ->enum('procedure_type', [
                    'main_procedure',
                    'sub_procedure',
                    'expert_procedure',
                ])
                ->default('main_procedure');
            $table->uuid('parent_case_id')->nullable(); // For sub-procedures
            $table->decimal('dispute_value', 15, 2)->nullable();
            $table->char('currency', 3)->default('EUR'); // ISO currency code
            $table->date('initiated_at');
            $table->date('deadline_decision')->nullable();
            $table->date('closed_at')->nullable();
            $table->json('arbitration_rules')->nullable(); // Schiedsordnung
            $table->text('internal_notes')->nullable(); // Referee notes
            $table->uuid('referee_id')->nullable();

            // Enhanced procedure information
            $table->string('jurisdiction')->nullable()->comment('Zuständigkeit (z.B. Deutschland, Schweiz)');
            $table->string('applicable_law')->nullable()->comment('Anwendbares Recht');
            $table->string('arbitration_institution')->nullable()->comment('Schiedsinstitution');
            $table->string('arbitration_rules_version')->nullable()->comment('Version der Schiedsordnung');
            $table->string('arbitration_agreement_file')->nullable();
            $table->text('arbitration_agreement')->nullable()->comment('Schiedsvereinbarung Text');

            // Enhanced financial information
            $table->decimal('advance_payment', 15, 2)->nullable()->comment('Vorschusszahlung');
            $table->decimal('arbitration_fees', 15, 2)->nullable()->comment('Schiedsgerichtskosten');
            $table->decimal('expert_fees', 15, 2)->nullable()->comment('Sachverständigenkosten');
            $table->decimal('other_costs', 15, 2)->nullable()->comment('Sonstige Kosten');
            $table->string('cost_distribution')->nullable()->comment('Kostenverteilung (z.B. "50/50", "Verlierer trägt")');

            // Enhanced timeline and deadlines
            $table->date('deadline_statement_claim')->nullable()->comment('Frist für Klageschrift');
            $table->date('deadline_statement_defense')->nullable()->comment('Frist für Klageerwiderung');
            $table->date('deadline_evidence')->nullable()->comment('Frist für Beweiseinreichung');
            $table->date('deadline_expert_opinion')->nullable()->comment('Frist für Gutachten');
            $table->date('deadline_hearing')->nullable()->comment('Frist für Anhörung');

            // Procedural milestones
            $table->json('procedural_milestones')->nullable()->comment('Verfahrensmeilensteine');
            $table->json('automatic_deadlines')->nullable()->comment('Automatische Fristen');
            $table->json('workflow_rules')->nullable()->comment('Workflow-Regeln');

            // Enhanced case classification
            $table->string('case_category')->nullable()->comment('Fallkategorie (z.B. Bau, Handel, Arbeitsrecht)');
            $table->string('complexity_level')->nullable()->comment('Komplexitätsgrad (einfach, mittel, hoch)');
            $table->string('urgency_level')->nullable()->comment('Dringlichkeitsgrad (normal, dringend, sehr dringend)');

            // Settlement and enforcement
            $table->text('settlement_terms')->nullable()->comment('Vergleichsbedingungen');
            $table->date('settlement_date')->nullable()->comment('Vergleichsdatum');
            $table->text('enforcement_details')->nullable()->comment('Vollstreckungsdetails');

            // Quality and review
            $table->string('quality_score')->nullable()->comment('Qualitätsbewertung');
            $table->text('review_notes')->nullable()->comment('Prüfungsnotizen');
            $table->uuid('reviewed_by')->nullable()->comment('Geprüft von');

            // Metadata
            $table->json('tags')->nullable()->comment('Tags für Kategorisierung');
            $table->json('custom_fields')->nullable()->comment('Benutzerdefinierte Felder');
            $table->timestamp('last_activity_at')->nullable()->comment('Letzte Aktivität');

            $table->timestamps();

            // Foreign keys will be added after users table exists
            // Indexes (case_number already indexed via unique constraint)
            $table->index(['status', 'initiated_at']);
            $table->index(['status', 'complexity_level']);
            $table->index(['jurisdiction', 'case_category']);
            $table->index(['deadline_decision', 'urgency_level']);
            $table->index('last_activity_at');
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
