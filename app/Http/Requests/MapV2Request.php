<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MapV2Request extends FormRequest
{
    public function authorize(): bool
    {
        // Routes are already protected by auth middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'galaxy_id' => ['nullable', 'integer', 'min:1'],
            'admin' => ['sometimes', 'boolean'],
            'as_commander_id' => ['nullable', 'integer', 'exists:commanders,id'],
            'debug' => ['sometimes', 'boolean'],
            // Optional filters
            'systems_relations' => ['sometimes', 'string'], // comma-separated: self,ally,neutral,unknown
            'fleets_relations' => ['sometimes', 'string'],
            'fleet_min_size' => ['sometimes', 'integer', 'min:0'],
            'fleet_max_size' => ['sometimes', 'integer', 'min:0'],
            'fleet_owner_id' => ['sometimes', 'integer', 'exists:commanders,id'],
        ];
    }

    public function validatedFilters(): array
    {
        $rels = ['self','ally','neutral','unknown'];
        $parseList = function (?string $csv) use ($rels) {
            if (!$csv) return null;
            $arr = array_values(array_filter(array_map('trim', explode(',', $csv))));
            $arr = array_values(array_intersect($arr, $rels));
            return empty($arr) ? null : $arr;
        };

        return [
            'systems_rel' => $parseList((string) $this->input('systems_relations', '')),
            'fleets_rel' => $parseList((string) $this->input('fleets_relations', '')),
            'fleet_min_size' => $this->has('fleet_min_size') ? (int) $this->input('fleet_min_size') : null,
            'fleet_max_size' => $this->has('fleet_max_size') ? (int) $this->input('fleet_max_size') : null,
            'fleet_owner_id' => $this->has('fleet_owner_id') ? (int) $this->input('fleet_owner_id') : null,
        ];
    }
}
