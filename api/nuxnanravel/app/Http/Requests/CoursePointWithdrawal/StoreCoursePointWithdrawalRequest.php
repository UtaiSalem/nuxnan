<?php

namespace App\Http\Requests\CoursePointWithdrawal;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoursePointWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $this->user() && ($this->user()->id === $course->user_id || $course->isAdmin($this->user()));
    }

    public function rules(): array
    {
        return ['amount' => 'required|integer|min:24000|max:10000000', 'purpose' => 'nullable|string|max:500'];
    }
}
