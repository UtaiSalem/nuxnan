<?php

namespace App\Http\Requests\Election;

use App\Models\Election;
use Illuminate\Foundation\Http\FormRequest;

class TransitionElectionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => 'required|in:'.implode(',', [Election::STATUS_DRAFT, Election::STATUS_NOMINATION, Election::STATUS_CAMPAIGN, Election::STATUS_VOTING, Election::STATUS_CLOSED, Election::STATUS_PUBLISHED, Election::STATUS_CANCELLED])];
    }

    public function messages(): array
    {
        return ['status.required' => 'กรุณาระบุสถานะใหม่', 'status.in' => 'สถานะการเลือกตั้งไม่ถูกต้อง'];
    }
}
