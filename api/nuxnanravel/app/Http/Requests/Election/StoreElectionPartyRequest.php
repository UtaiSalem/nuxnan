<?php

namespace App\Http\Requests\Election;

use Illuminate\Foundation\Http\FormRequest;

class StoreElectionPartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:120', 'slogan' => 'nullable|string|max:200', 'policy' => 'nullable|string', 'number' => 'nullable|integer|min:1', 'logo' => 'nullable|image|max:5120', 'members' => 'required|array|min:1', 'members.*.user_id' => 'required|integer|distinct|exists:users,id', 'members.*.role' => 'required|in:leader,deputy,secretary,treasurer,member', 'members.*.position_label' => 'nullable|string|max:80', 'members.*.sort_order' => 'nullable|integer|min:0'];
    }
}
