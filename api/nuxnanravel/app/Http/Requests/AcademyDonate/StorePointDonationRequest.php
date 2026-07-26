<?php

namespace App\Http\Requests\AcademyDonate;

use App\Policies\AcademyDonatePolicy;
use Illuminate\Foundation\Http\FormRequest;

class StorePointDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(AcademyDonatePolicy::class)->donate($this->user(), $this->route('academy'));
    }

    public function rules(): array
    {
        return ['points_amount' => 'required|integer|min:1|max:1000000', 'purpose' => 'nullable|string|max:500', 'anonymous' => 'boolean', 'donor_display_name' => 'nullable|string|max:255'];
    }

    public function messages(): array
    {
        return [];
    }
}
