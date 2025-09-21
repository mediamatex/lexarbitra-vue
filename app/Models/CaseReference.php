<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseReference extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_case_id',
        'database_name',
        'database_user',
        'database_password',
        'database_host',
        'connection_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function generateConnectionName(): string
    {
        return 'case_'.str_replace('-', '_', $this->id);
    }

    // Relationships
    public function participants()
    {
        return $this->hasMany(CaseParticipant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'case_participants')
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

    // Helper methods
    public function addParticipant(User $user, string $role, bool $isPrimary = false, ?string $notes = null): CaseParticipant
    {
        return $this->participants()->create([
            'user_id' => $user->id,
            'role' => $role,
            'is_primary' => $isPrimary,
            'appointed_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function removeParticipant(User $user, ?string $role = null): bool
    {
        $query = $this->participants()
            ->where('user_id', $user->id)
            ->whereNull('removed_at');

        if ($role) {
            $query->where('role', $role);
        }

        $updated = $query->update(['removed_at' => now()]);
        return $updated > 0;
    }

    public function hasParticipant(User $user, ?string $role = null): bool
    {
        $query = $this->participants()
            ->where('user_id', $user->id)
            ->active();

        if ($role) {
            $query->where('role', $role);
        }

        return $query->exists();
    }

    public function getReferees()
    {
        return $this->participants()
            ->active()
            ->withRole(CaseParticipant::REFEREE_ROLES)
            ->with('user')
            ->get();
    }

    public function getParties()
    {
        return $this->participants()
            ->active()
            ->withRole(CaseParticipant::PARTY_ROLES)
            ->with('user')
            ->get();
    }

}
