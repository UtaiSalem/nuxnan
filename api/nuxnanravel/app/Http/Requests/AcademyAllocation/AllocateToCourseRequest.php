<?php

namespace App\Http\Requests\AcademyAllocation;

use Illuminate\Foundation\Http\FormRequest;

class AllocateToCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academy = $this->route('academy');

        return $this->user()->id === $academy->user_id || $academy->isAdmin($this->user());
    }

    public function rules(): array
    {
        return ['course_id' => 'required|integer|exists:courses,id', 'amount' => 'required|integer|min:1|max:1000000000', 'purpose' => 'nullable|string|max:500'];
    }
}
