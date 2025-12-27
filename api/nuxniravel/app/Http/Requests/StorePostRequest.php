<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Basic content
            'content'               => 'nullable|string|max:5000',
            'privacy_settings'      => 'required|integer|in:1,2,3',
            
            // Images
            'images'                => 'nullable|array|max:20',
            'images.*'              => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
            'image_captions'        => 'nullable|array|max:20',
            'image_captions.*'      => 'nullable|string|max:500',
            
            // Location - simple string
            'location_name'         => 'nullable|string|max:255',
            
            // Location - structured
            'location'              => 'nullable|array',
            'location.name'         => 'required_with:location|string|max:255',
            'location.address'      => 'nullable|string|max:500',
            'location.city'         => 'nullable|string|max:100',
            'location.state'        => 'nullable|string|max:100',
            'location.country'      => 'nullable|string|max:100',
            'location.postal_code'  => 'nullable|string|max:20',
            'location.latitude'     => 'nullable|numeric|between:-90,90',
            'location.longitude'    => 'nullable|numeric|between:-180,180',
            'location.place_id'     => 'nullable|string|max:255',
            'location.place_type'   => 'nullable|string|max:50',
            'location.icon_url'     => 'nullable|url|max:500',
            
            // Feeling/Activity
            'feeling'               => 'nullable|string|max:50',
            'feeling_icon'          => 'nullable|string|max:50',
            'activity_type'         => 'nullable|string|max:50',
            'activity_text'         => 'nullable|string|max:255',
            
            // Background/Theme (for text-only status posts)
            'background_color'      => 'nullable|string|max:20|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'background_gradient'   => 'nullable|string|max:255',
            'background_image'      => 'nullable|url|max:500',
            'text_color'            => 'nullable|string|max:20|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'font_size'             => 'nullable|string|in:small,medium,large,xlarge',
            'background_id'         => 'nullable|integer|exists:post_backgrounds,id',
            
            // Tagged users (friends)
            'tagged_users'          => 'nullable|array|max:50',
            'tagged_users.*'        => 'integer|exists:users,id',
            
            // Scheduling
            'scheduled_at'          => 'nullable|date|after:now',
            
            // Options
            'comments_disabled'     => 'nullable|boolean',
            'is_pinned'             => 'nullable|boolean',
            
            // Poll reference
            'poll_id'               => 'nullable|integer|exists:polls,id',
            
            // Points (for sponsored posts)
            'point'                 => 'nullable|integer|min:1',
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
            // Content
            'content.max' => 'เนื้อหาโพสต์ต้องมีความยาวไม่เกิน 5000 ตัวอักษร / Post content must not exceed 5000 characters',
            
            // Privacy
            'privacy_settings.required' => 'กรุณาเลือกการตั้งค่าความเป็นส่วนตัว / Please select privacy setting',
            'privacy_settings.in' => 'การตั้งค่าความเป็นส่วนตัวไม่ถูกต้อง / Invalid privacy setting',
            
            // Images
            'images.max' => 'อัปโหลดรูปภาพได้ไม่เกิน 20 รูป / Maximum 20 images allowed',
            'images.*.image' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น / File must be an image',
            'images.*.mimes' => 'รูปภาพต้องเป็นไฟล์ประเภท jpeg, png, jpg, gif, svg หรือ webp / Image must be jpeg, png, jpg, gif, svg, or webp',
            'images.*.max' => 'ขนาดรูปภาพต้องไม่เกิน 4MB / Image size must not exceed 4MB',
            'image_captions.*.max' => 'คำบรรยายรูปภาพต้องไม่เกิน 500 ตัวอักษร / Image caption must not exceed 500 characters',
            
            // Location
            'location_name.max' => 'ชื่อสถานที่ต้องไม่เกิน 255 ตัวอักษร / Location name must not exceed 255 characters',
            'location.name.required_with' => 'กรุณาระบุชื่อสถานที่ / Please provide location name',
            'location.latitude.between' => 'ละติจูดต้องอยู่ระหว่าง -90 ถึง 90 / Latitude must be between -90 and 90',
            'location.longitude.between' => 'ลองจิจูดต้องอยู่ระหว่าง -180 ถึง 180 / Longitude must be between -180 and 180',
            
            // Feeling/Activity
            'feeling.max' => 'ความรู้สึกต้องไม่เกิน 50 ตัวอักษร / Feeling must not exceed 50 characters',
            'activity_type.in' => 'ประเภทกิจกรรมไม่ถูกต้อง / Invalid activity type',
            'activity_text.max' => 'ข้อความกิจกรรมต้องไม่เกิน 255 ตัวอักษร / Activity text must not exceed 255 characters',
            
            // Background
            'background_color.regex' => 'รูปแบบสีพื้นหลังไม่ถูกต้อง / Invalid background color format',
            'text_color.regex' => 'รูปแบบสีข้อความไม่ถูกต้อง / Invalid text color format',
            'font_size.in' => 'ขนาดตัวอักษรต้องเป็น small, medium, large หรือ xlarge / Font size must be small, medium, large, or xlarge',
            'background_id.exists' => 'รูปแบบพื้นหลังไม่ถูกต้อง / Invalid background template',
            
            // Tagged users
            'tagged_users.max' => 'แท็กเพื่อนได้สูงสุด 50 คน / Maximum 50 friends can be tagged',
            'tagged_users.*.exists' => 'ไม่พบผู้ใช้ที่ต้องการแท็ก / Tagged user not found',
            
            // Scheduling
            'scheduled_at.date' => 'รูปแบบวันที่กำหนดเวลาไม่ถูกต้อง / Invalid scheduled date format',
            'scheduled_at.after' => 'เวลาที่กำหนดต้องเป็นอนาคต / Scheduled time must be in the future',
            
            // Options
            'comments_disabled.boolean' => 'ค่าปิดคอมเมนต์ไม่ถูกต้อง / Invalid comments disabled value',
            'is_pinned.boolean' => 'ค่าปักหมุดไม่ถูกต้อง / Invalid pin value',
            
            // Poll
            'poll_id.exists' => 'ไม่พบโพลที่ระบุ / Poll not found',
        ];
    }

    /**
     * Custom validation to ensure at least content or images is provided.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasContent = !empty($this->content) && strlen(trim($this->content)) > 0;
            $hasImages = $this->hasFile('images') && count($this->file('images')) > 0;
            $hasPoll = !empty($this->poll_id);
            $hasBackground = !empty($this->background_color) || !empty($this->background_gradient) || !empty($this->background_id);
            
            // Must have at least one of: content, images, poll
            if (!$hasContent && !$hasImages && !$hasPoll) {
                $validator->errors()->add('content', 'โพสต์ต้องมีเนื้อหา รูปภาพ หรือโพลอย่างน้อย 1 รายการ / Post must have content, images, or a poll');
            }
            
            // Background can only be used with text-only posts (no images)
            if ($hasBackground && $hasImages) {
                $validator->errors()->add('background_color', 'ไม่สามารถใช้พื้นหลังกับโพสต์ที่มีรูปภาพ / Background cannot be used with image posts');
            }
            
            // Activity text requires activity type
            if (!empty($this->activity_text) && empty($this->activity_type)) {
                $validator->errors()->add('activity_type', 'กรุณาเลือกประเภทกิจกรรม / Please select activity type');
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert boolean strings to actual booleans
        if ($this->has('comments_disabled')) {
            $this->merge([
                'comments_disabled' => filter_var($this->comments_disabled, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
        
        if ($this->has('is_pinned')) {
            $this->merge([
                'is_pinned' => filter_var($this->is_pinned, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}

