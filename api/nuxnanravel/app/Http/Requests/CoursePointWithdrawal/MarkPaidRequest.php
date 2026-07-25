<?php

namespace App\Http\Requests\CoursePointWithdrawal;

use Illuminate\Foundation\Http\FormRequest;

class MarkPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isPlearndAdmin() || $this->user()->isSuperAdmin());
    }

    public function rules(): array
    {
        return ['payment_reference' => 'nullable|string|max:200'];
    }
}
