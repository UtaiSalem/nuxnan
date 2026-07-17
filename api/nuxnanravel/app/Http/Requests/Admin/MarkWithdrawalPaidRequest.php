<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MarkWithdrawalPaidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'payment_reference' => 'nullable|string|max:100',
            'proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ];
    }
}
