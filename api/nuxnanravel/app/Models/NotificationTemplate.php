<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NotificationTemplate Model - เทมเพลตการแจ้งเตือน
 */
class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'code',
        'name',
        'category',
        'channel',
        'subject',
        'body',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    // Category constants
    const CATEGORY_ACADEMIC = 'academic';

    const CATEGORY_FINANCIAL = 'financial';

    const CATEGORY_ATTENDANCE = 'attendance';

    const CATEGORY_EVENT = 'event';

    const CATEGORY_SYSTEM = 'system';

    // Channel constants
    const CHANNEL_EMAIL = 'email';

    const CHANNEL_SMS = 'sms';

    const CHANNEL_PUSH = 'push';

    const CHANNEL_IN_APP = 'in_app';

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    // Methods
    public function render(array $data): array
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($data as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }
}
