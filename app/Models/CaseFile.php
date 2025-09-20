<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CaseFile extends Model
{
    use HasFactory, HasUuids;

    // Workflow Status Constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_PENDING = 'pending';
    public const STATUS_HEARING_SCHEDULED = 'hearing_scheduled';
    public const STATUS_UNDER_DELIBERATION = 'under_deliberation';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_DECIDED = 'decided';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'case_number',
        'title',
        'description',
        'status',
        'procedure_type',
        'parent_case_id',
        'referee_id',
        'dispute_value',
        'currency',
        'initiated_at',
        'deadline_decision',
        'closed_at',
        'arbitration_rules',
        'internal_notes',
        'jurisdiction',
        'applicable_law',
        'arbitration_institution',
        'arbitration_rules_version',
        'arbitration_agreement',
        'arbitration_agreement_file',
        'advance_payment',
        'arbitration_fees',
        'expert_fees',
        'other_costs',
        'cost_distribution',
        'deadline_statement_claim',
        'deadline_statement_defense',
        'deadline_evidence',
        'deadline_expert_opinion',
        'deadline_hearing',
        'procedural_milestones',
        'automatic_deadlines',
        'workflow_rules',
        'case_category',
        'complexity_level',
        'urgency_level',
        'settlement_terms',
        'settlement_date',
        'enforcement_details',
        'quality_score',
        'review_notes',
        'reviewed_by',
        'tags',
        'custom_fields',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'initiated_at' => 'date',
            'deadline_decision' => 'date',
            'closed_at' => 'date',
            'deadline_statement_claim' => 'date',
            'deadline_statement_defense' => 'date',
            'deadline_evidence' => 'date',
            'deadline_expert_opinion' => 'date',
            'deadline_hearing' => 'date',
            'settlement_date' => 'date',
            'last_activity_at' => 'datetime',
            'arbitration_rules' => 'array',
            'procedural_milestones' => 'array',
            'automatic_deadlines' => 'array',
            'workflow_rules' => 'array',
            'tags' => 'array',
            'custom_fields' => 'array',
            'dispute_value' => 'decimal:2',
            'advance_payment' => 'decimal:2',
            'arbitration_fees' => 'decimal:2',
            'expert_fees' => 'decimal:2',
            'other_costs' => 'decimal:2',
        ];
    }

    // Relationships
    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function parentCase(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'parent_case_id');
    }

    public function subCases(): HasMany
    {
        return $this->hasMany(CaseFile::class, 'parent_case_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CaseParticipant::class);
    }

    public function parties(): HasMany
    {
        return $this->hasMany(CaseParty::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function databaseConnection(): HasOne
    {
        return $this->hasOne(CaseDatabaseConnection::class);
    }

    // Helper methods
    public function isMainProcedure(): bool
    {
        return $this->procedure_type === 'main_procedure';
    }

    public function isSubProcedure(): bool
    {
        return $this->procedure_type === 'sub_procedure';
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_INITIATED,
            self::STATUS_PENDING,
            self::STATUS_HEARING_SCHEDULED,
            self::STATUS_UNDER_DELIBERATION,
        ]);
    }
}
