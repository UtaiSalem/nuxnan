<?php

namespace App\Http\Requests\AcademyDonate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePointDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('donate', $this->route('academy'));
    }

    public function rules(): array
    {
        return ['points_amount' => 'required|integer|min:1|max:1000000', 'purpose' => 'nullable|string|max:500', 'anonymous' => 'boolean', 'donor_display_name' => 'nullable|string|max:255'];
    }
}
