<?php

namespace App\Http\Requests\AcademyDonate;

use App\Policies\AcademyDonatePolicy;
use Illuminate\Foundation\Http\FormRequest;

class StoreCashDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(AcademyDonatePolicy::class)->donate($this->user(), $this->route('academy'));
    }

    public function rules(): array
    {
        return ['cash_amount' => 'required|numeric|min:1|max:1000000', 'slip' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', 'payment_method' => 'required|string|max:32', 'payment_reference' => 'nullable|string|max:100', 'purpose' => 'nullable|string|max:500', 'anonymous' => 'boolean', 'donor_display_name' => 'nullable|string|max:255'];
    }

    /**
     * Multipart form values arrive as strings ("true"/"false"), which the
     * `boolean` rule rejects. Normalize to a real boolean before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('anonymous')) {
            $this->merge(['anonymous' => filter_var($this->input('anonymous'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }
}
