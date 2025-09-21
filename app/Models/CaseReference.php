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
            'route_key_name' => $this->getRouteKeyName(),
        ]);

        // Check if value looks like a valid UUID
        if (! $value || ! is_string($value)) {
            logger()->error('CaseReference::resolveRouteBinding - Invalid value provided', [
                'value' => $value,
                'value_type' => gettype($value),
                'url' => request()->url(),
            ]);

            return null;
        }

        // Log the query we're about to execute
        $fieldToSearch = $field ?? $this->getRouteKeyName();
        logger()->info('CaseReference::resolveRouteBinding - About to query database', [
            'field' => $fieldToSearch,
            'value' => $value,
            'table' => $this->getTable(),
        ]);

        try {
            $caseReference = $this->where($fieldToSearch, $value)->first();

            if ($caseReference) {
                logger()->info('CaseReference::resolveRouteBinding - Found case reference', [
                    'case_reference_id' => $caseReference->id,
                    'tenant_case_id' => $caseReference->tenant_case_id,
                    'database_name' => $caseReference->database_name,
                    'is_active' => $caseReference->is_active,
                    'lookup_value' => $value,
                ]);
            } else {
                // Log more details about why it wasn't found
                $totalCases = $this->count();
                $allCaseIds = $this->limit(10)->pluck('id')->toArray();

                logger()->error('CaseReference::resolveRouteBinding - Case reference not found', [
                    'lookup_value' => $value,
                    'field' => $fieldToSearch,
                    'url' => request()->url(),
                    'total_cases_in_db' => $totalCases,
                    'sample_case_ids' => $allCaseIds,
                    'value_length' => strlen($value),
                ]);
            }

            return $caseReference;

        } catch (\Exception $e) {
            logger()->error('CaseReference::resolveRouteBinding - Database query failed', [
                'value' => $value,
                'field' => $fieldToSearch,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
