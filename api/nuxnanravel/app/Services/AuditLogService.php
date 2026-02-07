<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log an action.
     */
    public function log(
        string $action,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $module = null,
        ?array $metadata = null
    ): AuditLog {
        $request = Request::instance();
        
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entity ? get_class($entity) : null,
            'entity_id' => $entity?->id,
            'module' => $module ?? $this->detectModule($entity),
            'old_values' => $oldValues ? $this->sanitizeValues($oldValues) : null,
            'new_values' => $newValues ? $this->sanitizeValues($newValues) : null,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log a create action.
     */
    public function logCreate(Model $entity, ?string $module = null, ?array $metadata = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATED,
            $entity,
            null,
            $entity->toArray(),
            $module,
            $metadata
        );
    }

    /**
     * Log an update action.
     */
    public function logUpdate(Model $entity, array $oldValues, ?string $module = null, ?array $metadata = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_UPDATED,
            $entity,
            $oldValues,
            $entity->toArray(),
            $module,
            $metadata
        );
    }

    /**
     * Log a delete action.
     */
    public function logDelete(Model $entity, ?string $module = null, ?array $metadata = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DELETED,
            $entity,
            $entity->toArray(),
            null,
            $module,
            $metadata
        );
    }

    /**
     * Log a view action.
     */
    public function logView(Model $entity, ?string $module = null, ?array $metadata = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_VIEWED,
            $entity,
            null,
            null,
            $module,
            $metadata
        );
    }

    /**
     * Log a login action.
     */
    public function logLogin(?int $userId = null, bool $success = true, ?array $metadata = null): AuditLog
    {
        $request = Request::instance();
        
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $success ? AuditLog::ACTION_LOGIN : AuditLog::ACTION_LOGIN_FAILED,
            'module' => 'auth',
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log a logout action.
     */
    public function logLogout(?int $userId = null, ?array $metadata = null): AuditLog
    {
        $request = Request::instance();
        
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => AuditLog::ACTION_LOGOUT,
            'module' => 'auth',
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log an export action.
     */
    public function logExport(string $exportType, ?array $filters = null, ?string $module = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_EXPORTED,
            null,
            null,
            null,
            $module,
            [
                'export_type' => $exportType,
                'filters' => $filters,
            ]
        );
    }

    /**
     * Log an import action.
     */
    public function logImport(string $importType, int $recordsCount, ?string $module = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_IMPORTED,
            null,
            null,
            null,
            $module,
            [
                'import_type' => $importType,
                'records_count' => $recordsCount,
            ]
        );
    }

    /**
     * Log an approval action.
     */
    public function logApproval(Model $entity, bool $approved, ?string $reason = null, ?string $module = null): AuditLog
    {
        return $this->log(
            $approved ? AuditLog::ACTION_APPROVED : AuditLog::ACTION_REJECTED,
            $entity,
            null,
            null,
            $module,
            ['reason' => $reason]
        );
    }

    /**
     * Log a custom action.
     */
    public function logCustom(
        string $action,
        ?Model $entity = null,
        ?array $metadata = null,
        ?string $module = null
    ): AuditLog {
        return $this->log($action, $entity, null, null, $module, $metadata);
    }

    /**
     * Detect module from entity type.
     */
    protected function detectModule(?Model $entity): ?string
    {
        if (!$entity) {
            return null;
        }

        $className = class_basename($entity);

        $moduleMap = [
            // User & Auth
            'User' => 'users',
            'Role' => 'users',
            'Permission' => 'users',
            
            // Academy & Learning
            'Academy' => 'academies',
            'AcademyMember' => 'academies',
            'Course' => 'courses',
            'CourseMember' => 'courses',
            'Lesson' => 'lessons',
            'Topic' => 'lessons',
            'Assignment' => 'assignments',
            'Quiz' => 'quizzes',
            
            // Students
            'Student' => 'students',
            'StudentGuardian' => 'students',
            'StudentHealthInfo' => 'students',
            'StudentHomeVisit' => 'students',
            
            // Staff
            'StaffProfile' => 'staff',
            'StaffAttendance' => 'staff',
            'LeaveRequest' => 'staff',
            
            // Finance
            'TuitionFee' => 'finance',
            'Payment' => 'finance',
            'Expense' => 'finance',
            'Scholarship' => 'finance',
            
            // Gamification
            'PointsTransaction' => 'points',
            'WalletTransaction' => 'wallet',
            'Achievement' => 'achievements',
            'Reward' => 'rewards',
            
            // Communication
            'Post' => 'posts',
            'Message' => 'messages',
            'Notification' => 'notifications',
        ];

        return $moduleMap[$className] ?? strtolower($className . 's');
    }

    /**
     * Sanitize values to remove sensitive data.
     */
    protected function sanitizeValues(array $values): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'secret',
            'token',
            'api_key',
            'api_secret',
            'credit_card',
            'cvv',
            'pin',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($values[$field])) {
                $values[$field] = '[REDACTED]';
            }
        }

        return $values;
    }

    /**
     * Clean old audit logs (retention policy).
     */
    public function cleanOldLogs(int $retentionDays = 365): int
    {
        return AuditLog::where('created_at', '<', now()->subDays($retentionDays))->delete();
    }

    /**
     * Get audit logs for an entity.
     */
    public function getEntityLogs(Model $entity, int $limit = 50)
    {
        return AuditLog::where('entity_type', get_class($entity))
            ->where('entity_id', $entity->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs for a user.
     */
    public function getUserLogs(int $userId, int $limit = 50)
    {
        return AuditLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs summary by action.
     */
    public function getSummaryByAction(string $startDate, string $endDate): array
    {
        return AuditLog::selectRaw('action, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();
    }

    /**
     * Get audit logs summary by module.
     */
    public function getSummaryByModule(string $startDate, string $endDate): array
    {
        return AuditLog::selectRaw('module, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('module')
            ->groupBy('module')
            ->pluck('count', 'module')
            ->toArray();
    }
}
