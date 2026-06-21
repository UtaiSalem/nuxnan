<?php

namespace App\Http\Requests\Academy\Rollover;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PreviewRolloverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('enrollment.preview', $this->route('academy'));
    }

    public function rules(): array
    {
        return [
            'from_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id')->where(fn ($query) => $query->where('academy_id', $this->academyId())),
            ],
            'to_year_id' => [
                'required',
                'integer',
                'different:from_year_id',
                Rule::exists('academic_years', 'id')->where(fn ($query) => $query->where('academy_id', $this->academyId())),
            ],
        ];
    }

    private function academyId(): int
    {
        return (int) ($this->route('academy')?->id ?? 0);
    }
}
