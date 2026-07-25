<?php

namespace App\Http\Requests\AcademyPointWithdrawal;

use Illuminate\Foundation\Http\FormRequest;

class ApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isPlearndAdmin() || $this->user()->isSuperAdmin());
    }

    public function rules(): array
    {
        return ['note' => 'nullable|string|max:1000'];
    }
}
