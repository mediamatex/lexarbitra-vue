<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseParticipant extends Model
{
    use HasFactory, HasUuids;

    // Role constants for better type safety and consistency
    public const ROLE_CHAIRMAN = 'chairman';
    public const ROLE_REFEREE = 'referee';
    public const ROLE_CO_REFEREE = 'co_referee';
    public const ROLE_CLAIMANT = 'claimant';
    public const ROLE_RESPONDENT = 'respondent';
    public const ROLE_EXPERT = 'expert';
    public const ROLE_WITNESS = 'witness';
    public const ROLE_ADMINISTRATOR = 'administrator';
    public const ROLE_LAWYER = 'lawyer';
    public const ROLE_ASSISTANT = 'assistant';

    // Role groups for easier permission checking
    public const REFEREE_ROLES = [
        self::ROLE_CHAIRMAN,
        self::ROLE_REFEREE,
        self::ROLE_CO_REFEREE,
    ];

    public const PARTY_ROLES = [
        self::ROLE_CLAIMANT,
        self::ROLE_RESPONDENT,
    ];

    public const EXPERT_ROLES = [
        self::ROLE_EXPERT,
        self::ROLE_WITNESS,
    ];

    public const ADMIN_ROLES = [
        self::ROLE_ADMINISTRATOR,
        self::ROLE_ASSISTANT,
    ];

    protected $fillable = [
        'case_reference_id',
        'user_id',
        'role',
        'is_primary',
        'appointed_at',
        'removed_at',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'appointed_at' => 'date',
        'removed_at' => 'date',
    ];

    // Relationships
    public function caseReference(): BelongsTo
    {
        return $this->belongsTo(CaseReference::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Permission checking methods
    public function canAccessInternalNotes(): bool
    {
        return in_array($this->role, [self::ROLE_CHAIRMAN, self::ROLE_REFEREE, self::ROLE_CO_REFEREE, self::ROLE_ADMINISTRATOR]);
    }

    public function canAccessInternalMessages(): bool
    {
        return in_array($this->role, [self::ROLE_CHAIRMAN, self::ROLE_REFEREE, self::ROLE_CO_REFEREE, self::ROLE_ADMINISTRATOR]);
    }

    public function canManageCase(): bool
    {
        return in_array($this->role, [self::ROLE_CHAIRMAN, self::ROLE_REFEREE]);
    }

    public function canUploadDocuments(): bool
    {
        return !in_array($this->role, [self::ROLE_WITNESS]); // Most roles can upload
    }

    public function canViewAllDocuments(): bool
    {
        return in_array($this->role, self::REFEREE_ROLES) || in_array($this->role, self::ADMIN_ROLES);
    }

    public function canSetDeadlines(): bool
    {
        return in_array($this->role, [self::ROLE_CHAIRMAN, self::ROLE_REFEREE]);
    }

    public function canCreateCalendarEvents(): bool
    {
        return in_array($this->role, [self::ROLE_CHAIRMAN, self::ROLE_REFEREE]);
    }

    public function canSendMessages(): bool
    {
        return true; // All participants can send messages
    }

    public function canViewAllMessages(): bool
    {
        return in_array($this->role, self::REFEREE_ROLES) || in_array($this->role, self::ADMIN_ROLES);
    }

    /**
     * Get role display names
     */
    public static function getRoleDisplayNames(): array
    {
        return [
            self::ROLE_CHAIRMAN => 'Vorsitzender',
            self::ROLE_REFEREE => 'Schiedsrichter',
            self::ROLE_CO_REFEREE => 'Co-Schiedsrichter',
            self::ROLE_CLAIMANT => 'Kläger',
            self::ROLE_RESPONDENT => 'Beklagte',
            self::ROLE_EXPERT => 'Experte',
            self::ROLE_WITNESS => 'Zeuge',
            self::ROLE_ADMINISTRATOR => 'Administrator',
            self::ROLE_LAWYER => 'Anwalt',
            self::ROLE_ASSISTANT => 'Assistent',
        ];
    }

    /**
     * Get detailed permissions for each role
     */
    public static function getRolePermissions(string $role): array
    {
        $permissions = [
            self::ROLE_CHAIRMAN => [
                'manage_case',
                'access_internal_notes',
                'access_internal_messages',
                'upload_documents',
                'view_all_documents',
                'set_deadlines',
                'create_calendar_events',
                'send_messages',
                'view_all_messages',
                'manage_participants',
                'create_decisions',
                'schedule_hearings',
            ],
            self::ROLE_REFEREE => [
                'manage_case',
                'access_internal_notes',
                'access_internal_messages',
                'upload_documents',
                'view_all_documents',
                'set_deadlines',
                'create_calendar_events',
                'send_messages',
                'view_all_messages',
                'create_decisions',
                'schedule_hearings',
            ],
            self::ROLE_CO_REFEREE => [
                'access_internal_notes',
                'access_internal_messages',
                'upload_documents',
                'view_all_documents',
                'send_messages',
                'view_all_messages',
            ],
            self::ROLE_CLAIMANT => [
                'upload_documents',
                'view_public_documents',
                'send_messages',
                'view_public_messages',
                'view_calendar',
            ],
            self::ROLE_RESPONDENT => [
                'upload_documents',
                'view_public_documents',
                'send_messages',
                'view_public_messages',
                'view_calendar',
            ],
            self::ROLE_EXPERT => [
                'upload_documents',
                'view_assigned_documents',
                'send_messages',
                'view_assigned_messages',
                'view_assigned_calendar',
            ],
            self::ROLE_WITNESS => [
                'view_assigned_documents',
                'send_messages',
                'view_assigned_messages',
            ],
            self::ROLE_ADMINISTRATOR => [
                'access_internal_notes',
                'access_internal_messages',
                'view_all_documents',
                'view_all_messages',
                'manage_participants',
                'manage_settings',
            ],
            self::ROLE_LAWYER => [
                'upload_documents',
                'view_public_documents',
                'send_messages',
                'view_public_messages',
                'view_calendar',
            ],
            self::ROLE_ASSISTANT => [
                'upload_documents',
                'view_assigned_documents',
                'send_messages',
                'view_assigned_messages',
                'view_calendar',
            ],
        ];

        return $permissions[$role] ?? [];
    }

    /**
     * Check if this participant has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        $rolePermissions = self::getRolePermissions($this->role);
        return in_array($permission, $rolePermissions);
    }

    /**
     * Get role description for user display
     */
    public static function getRoleDescription(string $role): string
    {
        $descriptions = [
            self::ROLE_CHAIRMAN => 'Leitet das Schiedsverfahren und trifft endgültige Entscheidungen',
            self::ROLE_REFEREE => 'Entscheidet über den Fall und erstellt Schiedssprüche',
            self::ROLE_CO_REFEREE => 'Unterstützt den Hauptschiedsrichter bei Entscheidungen',
            self::ROLE_CLAIMANT => 'Initiiert das Schiedsverfahren und stellt Ansprüche',
            self::ROLE_RESPONDENT => 'Verteidigt sich gegen die Ansprüche des Klägers',
            self::ROLE_EXPERT => 'Stellt Expertenwissen in spezifischen Bereichen zur Verfügung',
            self::ROLE_WITNESS => 'Gibt Zeugnis über relevante Fakten ab',
            self::ROLE_ADMINISTRATOR => 'Verwaltet das Verfahren und unterstützt organisatorisch',
            self::ROLE_LAWYER => 'Vertritt eine Partei rechtlich im Verfahren',
            self::ROLE_ASSISTANT => 'Unterstützt andere Teilnehmer bei der Verfahrensabwicklung',
        ];

        return $descriptions[$role] ?? 'Unbekannte Rolle';
    }

    /**
     * Check if the participant can be assigned to deadlines
     */
    public function canBeAssignedToDeadlines(): bool
    {
        // Exclude witnesses and experts from automatic deadline assignments
        return !in_array($this->role, [self::ROLE_WITNESS, self::ROLE_EXPERT]);
    }

    /**
     * Scope for active participants (not removed)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('removed_at');
    }

    /**
     * Scope for participants with specific roles
     */
    public function scopeWithRole($query, array|string $roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        return $query->whereIn('role', $roles);
    }
}