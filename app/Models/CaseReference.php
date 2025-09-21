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

    public function resolveRouteBinding($value, $field = null)
    {
        logger()->info('CaseReference::resolveRouteBinding - Start', [
            'value' => $value,
            'field' => $field,
            'url' => request()->url(),
            'method' => request()->method(),
        ]);

        $caseReference = $this->where($field ?? $this->getRouteKeyName(), $value)->first();

        if ($caseReference) {
            logger()->info('CaseReference::resolveRouteBinding - Found case reference', [
                'case_reference_id' => $caseReference->id,
                'tenant_case_id' => $caseReference->tenant_case_id,
                'database_name' => $caseReference->database_name,
                'is_active' => $caseReference->is_active,
                'lookup_value' => $value,
            ]);
        } else {
            logger()->warning('CaseReference::resolveRouteBinding - Case reference not found', [
                'lookup_value' => $value,
                'field' => $field ?? $this->getRouteKeyName(),
                'url' => request()->url(),
            ]);
        }

        return $caseReference;
    }
}
