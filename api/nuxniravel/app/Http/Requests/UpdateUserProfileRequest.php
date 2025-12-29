<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'        => ['nullable', 'string', 'max:100'],
            'last_name'         => ['nullable', 'string', 'max:100'],
            'bio'               => ['nullable', 'string', 'max:500'],
            'birthdate'         => ['nullable', 'date', 'before:today'],
            'gender'            => ['nullable', 'string', 'in:male,female,other,prefer_not_to_say'],
            'location'          => ['nullable', 'string', 'max:255'],
            'website'           => ['nullable', 'url', 'max:255'],
            'interests'         => ['nullable', 'string', 'max:1000'],
            'social_media_links'=> ['nullable', 'array'],
            'social_media_links.facebook'   => ['nullable', 'url'],
            'social_media_links.twitter'    => ['nullable', 'url'],
            'social_media_links.instagram'  => ['nullable', 'url'],
            'social_media_links.linkedin'   => ['nullable', 'url'],
            'social_media_links.youtube'    => ['nullable', 'url'],
            'social_media_links.tiktok'     => ['nullable', 'url'],
            'privacy_settings'  => ['nullable', 'string', 'in:public,friends,private'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'birthdate.before'  => 'วันเกิดต้องเป็นวันในอดีต',
            'website.url'       => 'รูปแบบ URL ไม่ถูกต้อง',
            'bio.max'           => 'Bio ต้องไม่เกิน 500 ตัวอักษร',
        ];
    }
}
