<?php

namespace App\Http\Requests\Election;

use Illuminate\Foundation\Http\FormRequest;

class StoreElectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['academic_year_id' => 'nullable|exists:academic_years,id', 'title' => 'required|string|max:150', 'description' => 'nullable|string', 'nomination_opens_at' => 'nullable|date', 'nomination_closes_at' => 'nullable|date|after_or_equal:nomination_opens_at', 'voting_opens_at' => 'nullable|date', 'voting_closes_at' => 'nullable|date|after_or_equal:voting_opens_at', 'allow_abstain' => 'nullable|boolean', 'ballot_ttl_seconds' => 'nullable|integer|min:1', 'settings' => 'nullable|array'];
    }

    public function messages(): array
    {
        return ['title.required' => 'กรุณาระบุชื่อการเลือกตั้ง', 'title.max' => 'ชื่อการเลือกตั้งต้องไม่เกิน 150 ตัวอักษร', 'academic_year_id.exists' => 'ไม่พบปีการศึกษาที่ระบุ', 'nomination_closes_at.after_or_equal' => 'วันปิดรับสมัครต้องไม่ก่อนวันเปิดรับสมัคร', 'voting_closes_at.after_or_equal' => 'วันปิดลงคะแนนต้องไม่ก่อนวันเปิดลงคะแนน'];
    }
}
