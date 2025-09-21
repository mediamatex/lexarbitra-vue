<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUuids, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'title',
        'law_firm',
        'phone',
        'address',
        'bar_number',
        'avatar_url',
        'is_active',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    // Relationships
    public function caseParticipations()
    {
        return $this->hasMany(CaseParticipant::class);
    }

    public function caseReferences()
    {
        return $this->belongsToMany(CaseReference::class, 'case_participants')
            ->withPivot([
                'role',
                'is_primary',
                'appointed_at',
                'removed_at',
                'notes',
            ])
            ->withTimestamps()
            ->wherePivotNull('removed_at');
    }

    // Role checking methods
    public function isRefereeInCase(?string $caseReferenceId = null): bool
    {
        $query = $this->caseParticipations()
            ->active()
            ->withRole(CaseParticipant::REFEREE_ROLES);

        if ($caseReferenceId) {
            $query->where('case_reference_id', $caseReferenceId);
        }

        return $query->exists();
    }

    public function canCreateCases(): bool
    {
        return $this->is_super_admin || $this->isRefereeInCase();
    }

    public function canManageCases(): bool
    {
        return $this->is_super_admin || $this->isRefereeInCase();
    }

    public function getRoleInCase(string $caseReferenceId): ?string
    {
        $participation = $this->caseParticipations()
            ->where('case_reference_id', $caseReferenceId)
            ->active()
            ->first();

        return $participation ? $participation->role : null;
    }

    public function getRoleDisplayInCase(string $caseReferenceId): ?string
    {
        $role = $this->getRoleInCase($caseReferenceId);
        if (!$role) {
            return null;
        }

        return CaseParticipant::getRoleDisplayNames()[$role] ?? $role;
    }

    public function getRoleDescriptionInCase(string $caseReferenceId): ?string
    {
        $role = $this->getRoleInCase($caseReferenceId);
        if (!$role) {
            return null;
        }

        return CaseParticipant::getRoleDescription($role);
    }

    public function getPermissionsInCase(string $caseReferenceId): array
    {
        if ($this->is_super_admin) {
            return ['*']; // Super admin has all permissions
        }

        $participation = $this->caseParticipations()
            ->where('case_reference_id', $caseReferenceId)
            ->active()
            ->first();

        if (!$participation) {
            return [];
        }

        return CaseParticipant::getRolePermissions($participation->role);
    }

    public function hasPermissionInCase(string $caseReferenceId, string $permission): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $permissions = $this->getPermissionsInCase($caseReferenceId);
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    public function isParticipantInCase(string $caseReferenceId): bool
    {
        return $this->caseParticipations()
            ->where('case_reference_id', $caseReferenceId)
            ->active()
            ->exists();
    }

    public function isPrimaryParticipantInCase(string $caseReferenceId): bool
    {
        return $this->caseParticipations()
            ->where('case_reference_id', $caseReferenceId)
            ->where('is_primary', true)
            ->active()
            ->exists();
    }

    public function canAccessCase(CaseReference $caseReference): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->isParticipantInCase($caseReference->id);
    }

    public function canEditCase(CaseReference $caseReference): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->hasPermissionInCase($caseReference->id, 'manage_case');
    }

    public function canDeleteCase(CaseReference $caseReference): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        // Only primary referees can delete cases
        $participation = $this->caseParticipations()
            ->where('case_reference_id', $caseReference->id)
            ->where('role', CaseParticipant::ROLE_REFEREE)
            ->where('is_primary', true)
            ->active()
            ->first();

        return $participation !== null;
    }
}
