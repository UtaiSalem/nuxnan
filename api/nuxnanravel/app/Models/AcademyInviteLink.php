<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AcademyInviteLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'created_by',
        'code',
        'name',
        'description',
        'academy_role_id',
        'max_uses',
        'use_count',
        'expires_at',
        'is_active',
        'require_approval',
        'allowed_domains',
        'metadata',
    ];

    protected $casts = [
        'max_uses' => 'integer',
        'use_count' => 'integer',
        'is_active' => 'boolean',
        'require_approval' => 'boolean',
        'expires_at' => 'datetime',
        'allowed_domains' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = self::generateUniqueCode();
            }
        });
    }

    /**
     * Generate unique invite code
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = Str::random(12);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(AcademyRole::class, 'academy_role_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeHasUsesRemaining($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('max_uses')
                ->orWhereRaw('use_count < max_uses');
        });
    }

    public function scopeValid($query)
    {
        return $query->active()->notExpired()->hasUsesRemaining();
    }

    /**
     * Check if link is valid
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        if ($this->max_uses && $this->use_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Get remaining uses
     */
    public function getRemainingUsesAttribute(): ?int
    {
        if ($this->max_uses === null) {
            return null; // Unlimited
        }

        return max(0, $this->max_uses - $this->use_count);
    }

    /**
     * Get status
     */
    public function getStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }
        if ($this->expires_at && $this->expires_at < now()) {
            return 'expired';
        }
        if ($this->max_uses && $this->use_count >= $this->max_uses) {
            return 'exhausted';
        }

        return 'active';
    }

    /**
     * Get full invite URL
     */
    public function getInviteUrlAttribute(): string
    {
        return config('app.frontend_url', 'https://app.plearnd.com').'/invite/'.$this->code;
    }

    /**
     * Get QR code URL (using external service)
     */
    public function getQrCodeUrlAttribute(): string
    {
        $url = urlencode($this->invite_url);

        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={$url}";
    }

    /**
     * Increment use count
     */
    public function incrementUseCount(): void
    {
        $this->increment('use_count');
    }

    /**
     * Check if email domain is allowed
     */
    public function isEmailAllowed(string $email): bool
    {
        if (empty($this->allowed_domains)) {
            return true;
        }

        $emailDomain = substr(strrchr($email, '@'), 1);

        return in_array($emailDomain, $this->allowed_domains);
    }
}
