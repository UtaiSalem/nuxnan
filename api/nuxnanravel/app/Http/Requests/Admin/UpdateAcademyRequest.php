<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateAcademyRequest
 *
 * ใช้แทน $request->all() ใน admin academy update route
 * ป้องกัน mass assignment และ validate ฟิลด์.
 */
class UpdateAcademyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'                   => ['sometimes', 'string', 'max:255'],
            'slogan'                 => ['sometimes', 'nullable', 'string', 'max:255'],
            'description'            => ['sometimes', 'nullable', 'string'],
            'address'                => ['sometimes', 'nullable', 'string', 'max:500'],
            'email'                  => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone'                  => ['sometimes', 'nullable', 'string', 'max:50'],
            'director'               => ['sometimes', 'nullable', 'string', 'max:255'],
            'established_year'       => ['sometimes', 'nullable', 'integer', 'min:1800', 'max:2200'],
            'type'                   => ['sometimes', 'nullable', 'string', 'max:100'],
            'accreditation'          => ['sometimes', 'nullable', 'string', 'max:100'],
            'accreditation_body'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'total_students'         => ['sometimes', 'integer', 'min:0'],
            'total_teachers'         => ['sometimes', 'integer', 'min:0'],
            'membership_fees_points' => ['sometimes', 'integer', 'min:0'],
            'courses_offered'        => ['sometimes', 'nullable', 'integer', 'min:0'],
            'facilities'             => ['sometimes', 'nullable', 'string'],
            'academy_timings'        => ['sometimes', 'nullable', 'string'],
            'holidays'               => ['sometimes', 'nullable', 'string'],
            'social_media_links'     => ['sometimes', 'nullable', 'string', 'max:500'],
            'logo'                   => ['sometimes', 'nullable', 'string', 'max:255'],
            'cover'                  => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
