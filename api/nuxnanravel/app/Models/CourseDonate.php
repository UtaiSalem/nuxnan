<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseDonate extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_POINT = 'point';

    public const TYPE_CASH = 'cash';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['course_id', 'donor_id', 'donor_display_name', 'donation_type', 'points_amount', 'remaining_points', 'cash_amount', 'currency', 'status', 'purpose', 'anonymous', 'slip_path', 'payment_method', 'payment_reference', 'idempotency_key', 'version', 'course_point_transaction_id', 'approved_by', 'reviewed_at', 'rejection_reason', 'metadata'];

    protected $casts = ['points_amount' => 'integer', 'remaining_points' => 'integer', 'cash_amount' => 'decimal:4', 'anonymous' => 'boolean', 'metadata' => 'array', 'reviewed_at' => 'datetime'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function coursePointTransaction()
    {
        return $this->belongsTo(CoursePointTransaction::class);
    }
}
