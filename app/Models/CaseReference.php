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
}
