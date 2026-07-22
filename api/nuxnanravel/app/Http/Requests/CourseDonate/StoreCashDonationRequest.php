<?php

namespace App\Http\Requests\CourseDonate;

use App\Policies\CourseDonatePolicy;
use Illuminate\Foundation\Http\FormRequest;

class StoreCashDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Course maps to CoursePolicy (which has no `donate` ability), so invoke
        // the CourseDonatePolicy directly instead of Gate::allows('donate', ...).
        return app(CourseDonatePolicy::class)->donate($this->user(), $this->route('course'));
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
