<?php

namespace App\Http\Requests\Election;

class UpdateElectionRequest extends StoreElectionRequest
{
    public function rules(): array
    {
        return array_map(fn ($r) => str_starts_with($r, 'required') ? 'sometimes|'.substr($r, 9) : $r, parent::rules());
    }
}
