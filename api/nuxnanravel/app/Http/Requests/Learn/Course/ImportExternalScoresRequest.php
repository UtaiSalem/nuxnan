<?php

namespace App\Http\Requests\Learn\Course;

use Illuminate\Foundation\Http\FormRequest;

class ImportExternalScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:2048'],
            'group_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'กรุณาเลือกไฟล์',
            'file.file' => 'ต้องเป็นไฟล์',
            'file.mimes' => 'รองรับเฉพาะไฟล์ .xlsx และ .csv',
            'file.max' => 'ไฟล์ต้องมีขนาดไม่เกิน 2 MB',
        ];
    }
}
