<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if the authenticated user owns this post
        $post = $this->route('post');
        return auth()->check() && $post && $post->user_id === auth()->id();
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
            'privacy_settings'      => 'nullable|integer|in:1,2,3',
            
            // Images (new ones to add)
            'images'                => 'nullable|array|max:20',
            'images.*'              => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
            'image_captions'        => 'nullable|array|max:20',
            'image_captions.*'      => 'nullable|string|max:500',
            
            // Images to delete
            'delete_images'         => 'nullable|array',
            'delete_images.*'       => 'integer|exists:post_images,id',
            
            // Reorder images
            'image_order'           => 'nullable|array',
            'image_order.*'         => 'integer|exists:post_images,id',
            
            // Location - simple string
            'location_name'         => 'nullable|string|max:255',
            
            // Location - structured (null to remove)
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
            
            // Feeling/Activity (null to remove)
            'feeling'               => 'nullable|string|max:50',
            'feeling_icon'          => 'nullable|string|max:50',
            'activity_type'         => 'nullable|string|max:50|in:watching,listening,reading,playing,eating,drinking,traveling,celebrating,attending,supporting,looking_for,thinking_about',
            'activity_text'         => 'nullable|string|max:255',
            'remove_feeling'        => 'nullable|boolean',
            
            // Background/Theme
            'background_color'      => 'nullable|string|max:20|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'background_gradient'   => 'nullable|string|max:255',
            'background_image'      => 'nullable|url|max:500',
            'text_color'            => 'nullable|string|max:20|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'font_size'             => 'nullable|string|in:small,medium,large,xlarge',
            'remove_background'     => 'nullable|boolean',
            
            // Tagged users
            'tagged_users'          => 'nullable|array|max:50',
            'tagged_users.*'        => 'integer|exists:users,id',
            
            // Options
            'comments_disabled'     => 'nullable|boolean',
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
            'privacy_settings.in' => 'การตั้งค่าความเป็นส่วนตัวไม่ถูกต้อง / Invalid privacy setting',
            
            // Images
            'images.max' => 'อัปโหลดรูปภาพได้ไม่เกิน 20 รูป / Maximum 20 images allowed',
            'images.*.image' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น / File must be an image',
            'images.*.mimes' => 'รูปภาพต้องเป็นไฟล์ประเภท jpeg, png, jpg, gif, svg หรือ webp',
            'images.*.max' => 'ขนาดรูปภาพต้องไม่เกิน 4MB / Image size must not exceed 4MB',
            'delete_images.*.exists' => 'รูปภาพที่ต้องการลบไม่พบ / Image to delete not found',
            
            // Location
            'location.name.required_with' => 'กรุณาระบุชื่อสถานที่ / Please provide location name',
            'location.latitude.between' => 'ละติจูดต้องอยู่ระหว่าง -90 ถึง 90',
            'location.longitude.between' => 'ลองจิจูดต้องอยู่ระหว่าง -180 ถึง 180',
            
            // Feeling/Activity
            'activity_type.in' => 'ประเภทกิจกรรมไม่ถูกต้อง / Invalid activity type',
            
            // Background
            'background_color.regex' => 'รูปแบบสีพื้นหลังไม่ถูกต้อง / Invalid background color format',
            'text_color.regex' => 'รูปแบบสีข้อความไม่ถูกต้อง / Invalid text color format',
            
            // Tagged users
            'tagged_users.max' => 'แท็กเพื่อนได้สูงสุด 50 คน / Maximum 50 friends can be tagged',
            'tagged_users.*.exists' => 'ไม่พบผู้ใช้ที่ต้องการแท็ก / Tagged user not found',
        ];
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
        
        if ($this->has('remove_feeling')) {
            $this->merge([
                'remove_feeling' => filter_var($this->remove_feeling, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
        
        if ($this->has('remove_background')) {
            $this->merge([
                'remove_background' => filter_var($this->remove_background, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
