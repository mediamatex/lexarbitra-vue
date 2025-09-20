<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseReference extends Model
{
    use HasUuids;

    protected $fillable = [
        'case_number',
        'title',
        'status',
        'initiated_at',
        'created_by',
        'tenant_case_id',
        'database_name',
        'database_user',
        'database_password',
        'database_host',
        'connection_name',
        'is_active',
    ];

    protected $casts = [
        'initiated_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function generateConnectionName(): string
    {
        return 'case_' . str_replace('-', '_', $this->id);
    }
}
