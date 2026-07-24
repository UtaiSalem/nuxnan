<?php

namespace App\Http\Requests\Campaign;

use App\Enums\CampaignScopeType;
use App\Enums\CampaignType;
use App\Models\Academy;
use App\Models\Course;
use App\Services\Campaign\CampaignAuthorizationService;
use App\Services\Campaign\CampaignPricingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $scope = (string) $this->input('scope_type', 'public');
        $academy = $this->input('academy_id') ? Academy::find($this->input('academy_id')) : null;
        $course = $this->input('course_id') ? Course::find($this->input('course_id')) : null;

        return auth()->check() && app(CampaignAuthorizationService::class)->canCreate(auth()->user(), $scope, $academy, $course);
    }

    public function rules(): array
    {
        return [
            'campaign_type' => ['required', Rule::in(array_column(CampaignType::cases(), 'value'))],
            'scope_type' => ['required', Rule::in(array_column(CampaignScopeType::cases(), 'value'))],
            'title' => ['required_if:campaign_type,advertisement', 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'media_link' => ['nullable', 'url', 'max:2048'],
            'media_image' => ['required_if:campaign_type,advertisement', 'file', 'mimes:jpg,jpeg,png,gif,mp4,webm,ogg', 'max:20480'],
            'academy_id' => ['nullable', 'integer', 'exists:academies,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'inherit_to_academy' => ['nullable', 'boolean'],
            'beneficiary_id' => ['nullable', 'integer', 'exists:users,id'],
            'duration' => ['required_if:campaign_type,advertisement', 'nullable', 'integer', Rule::in([5, 10, 15, 30, 60])],
            'total_views' => ['required_if:campaign_type,advertisement', 'nullable', 'integer', 'min:100', 'max:100000'],
            'budget_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(['wallet', 'slip'])],
            'slip' => ['required_if:payment_method,slip', 'nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
            'transfer_date' => ['required_if:payment_method,slip', 'nullable', 'date'],
            'transfer_time' => ['required_if:payment_method,slip', 'nullable', 'date_format:H:i'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $scope = $this->input('scope_type');
            $type = $this->input('campaign_type');
            $academyId = $this->input('academy_id');
            $courseId = $this->input('course_id');

            if ($scope === 'public' && ($academyId || $courseId)) {
                $validator->errors()->add('scope_type', 'แคมเปญ public ต้องไม่มี academy_id หรือ course_id');
            }
            if ($scope === 'academy' && (! $academyId || $courseId)) {
                $validator->errors()->add('academy_id', 'แคมเปญ academy ต้องระบุ academy_id และห้ามระบุ course_id');
            }
            if ($scope === 'course') {
                $course = $courseId ? Course::find($courseId) : null;
                if (! $course) {
                    $validator->errors()->add('course_id', 'กรุณาระบุรายวิชา');
                } elseif ($academyId && (int) $course->academy_id !== (int) $academyId) {
                    $validator->errors()->add('course_id', 'รายวิชาไม่ได้อยู่ในโรงเรียนที่ระบุ');
                }
            }
            if ($type === 'support' && $this->input('media_image')) {
                $validator->errors()->add('media_image', 'support ไม่รองรับสื่อโฆษณา');
            }
            if ($type === 'support' && (float) $this->input('budget_amount', 0) < 1) {
                $validator->errors()->add('budget_amount', 'งบสนับสนุนขั้นต่ำ 1 บาท');
            }
            if ($type === 'advertisement' && $this->filled(['duration', 'total_views', 'budget_amount'])) {
                $expected = app(CampaignPricingService::class)->advertisement((int) $this->total_views, (int) $this->duration);
                if (abs((float) $this->budget_amount - $expected) > 0.01) {
                    $validator->errors()->add('budget_amount', 'งบประมาณไม่ตรงกับราคาที่ระบบคำนวณ ('.$expected.')');
                }
            }
        });
    }
}
