<?php

namespace App\Http\Requests\CourseDonate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCashDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('donate', $this->route('course'));
    }

    public function rules(): array
    {
        return ['cash_amount' => 'required|numeric|min:1|max:1000000', 'slip' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', 'payment_method' => 'required|string|max:32', 'payment_reference' => 'nullable|string|max:100', 'purpose' => 'nullable|string|max:500', 'anonymous' => 'boolean', 'donor_display_name' => 'nullable|string|max:255'];
    }
}
