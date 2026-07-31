<?php

namespace App\Http\Requests\Election;

class UpdateElectionPartyRequest extends StoreElectionPartyRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), ['name' => 'sometimes|string|max:120', 'members' => 'sometimes|array|min:1']);
    }
}
