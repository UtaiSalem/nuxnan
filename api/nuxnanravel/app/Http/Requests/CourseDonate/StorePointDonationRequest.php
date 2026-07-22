<?php

namespace App\Http\Requests\CourseDonate;

use App\Policies\CourseDonatePolicy;
use Illuminate\Foundation\Http\FormRequest;

class StorePointDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Course maps to CoursePolicy (which has no `donate` ability), so invoke
        // the CourseDonatePolicy directly instead of Gate::allows('donate', ...).
        return app(CourseDonatePolicy::class)->donate($this->user(), $this->route('course'));
    }

    public function rules(): array
    {
        return ['points_amount' => 'required|integer|min:1|max:1000000', 'purpose' => 'nullable|string|max:500', 'anonymous' => 'boolean', 'donor_display_name' => 'nullable|string|max:255'];
    }
}
