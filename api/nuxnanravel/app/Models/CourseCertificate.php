<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CourseCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_number',
        'course_member_id',
        'course_id',
        'student_id',
        'type',
        'grade',
        'score',
        'grade_point',
        'student_name',
        'course_title',
        'instructor_name',
        'completion_date',
        'issue_date',
        'verification_code',
        'qr_code_path',
        'pdf_path',
        'thumbnail_path',
        'status',
        'issued_at',
        'issued_by',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'download_count',
        'last_downloaded_at',
        'expires_at',
    ];

    protected $casts = [
        'completion_date' => 'date',
        'issue_date' => 'date',
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'expires_at' => 'date',
        'score' => 'decimal:2',
        'grade_point' => 'decimal:2',
    ];

    // Certificate types
    const TYPE_COMPLETION = 'completion';
    const TYPE_ACHIEVEMENT = 'achievement';
    const TYPE_PARTICIPATION = 'participation';
    const TYPE_EXCELLENCE = 'excellence';

    // Status
    const STATUS_DRAFT = 'draft';
    const STATUS_ISSUED = 'issued';
    const STATUS_REVOKED = 'revoked';
    const STATUS_EXPIRED = 'expired';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($certificate) {
            if (empty($certificate->certificate_number)) {
                $certificate->certificate_number = self::generateCertificateNumber();
            }
            if (empty($certificate->verification_code)) {
                $certificate->verification_code = self::generateVerificationCode();
            }
        });
    }

    /**
     * Generate unique certificate number
     */
    public static function generateCertificateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $random = strtoupper(Str::random(6));
        $sequence = self::whereYear('created_at', $year)->count() + 1;
        
        return sprintf('CERT-%s%s-%06d-%s', $year, $month, $sequence, $random);
    }

    /**
     * Generate verification code
     */
    public static function generateVerificationCode(): string
    {
        return hash('sha256', Str::uuid() . now()->timestamp . Str::random(32));
    }

    /**
     * Course member
     */
    public function courseMember(): BelongsTo
    {
        return $this->belongsTo(CourseMember::class);
    }

    /**
     * วิชา
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * นักเรียน
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * ผู้ออกใบประกาศ
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * ผู้เพิกถอน
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Issue the certificate
     */
    public function issue(User $issuer): self
    {
        $this->update([
            'status' => self::STATUS_ISSUED,
            'issued_at' => now(),
            'issued_by' => $issuer->id,
            'issue_date' => now()->toDateString(),
        ]);

        return $this;
    }

    /**
     * Revoke the certificate
     */
    public function revoke(User $revoker, string $reason): self
    {
        $this->update([
            'status' => self::STATUS_REVOKED,
            'revoked_at' => now(),
            'revoked_by' => $revoker->id,
            'revocation_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Increment download count
     */
    public function recordDownload(): self
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);

        return $this;
    }

    /**
     * Check if certificate is valid
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_ISSUED) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get verification URL
     */
    public function getVerificationUrl(): string
    {
        return config('app.url') . '/verify-certificate/' . $this->verification_code;
    }

    /**
     * Scope: Issued certificates
     */
    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    /**
     * Scope: Valid certificates
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_ISSUED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Get type label in Thai
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_COMPLETION => 'ใบจบวิชา',
            self::TYPE_ACHIEVEMENT => 'ใบประกาศเกียรติคุณ',
            self::TYPE_PARTICIPATION => 'ใบรับรองการเข้าร่วม',
            self::TYPE_EXCELLENCE => 'ใบประกาศความเป็นเลิศ',
            default => $this->type,
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'ฉบับร่าง',
            self::STATUS_ISSUED => 'ออกแล้ว',
            self::STATUS_REVOKED => 'เพิกถอน',
            self::STATUS_EXPIRED => 'หมดอายุ',
            default => $this->status,
        };
    }
}
