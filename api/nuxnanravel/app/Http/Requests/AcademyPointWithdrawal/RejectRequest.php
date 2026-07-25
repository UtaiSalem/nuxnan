<?php

namespace App\Http\Requests\AcademyPointWithdrawal;

use Illuminate\Foundation\Http\FormRequest;

class RejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isPlearndAdmin() || $this->user()->isSuperAdmin());
    }

    public function rules(): array
    {
        return ['reason' => 'required|string|max:1000'];
    }
}
