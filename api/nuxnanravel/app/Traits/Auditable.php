<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Services\AuditLogService;

/**
 * Trait Auditable
 *
 * Add this trait to any model that should be automatically audited.
 *
 * Usage:
 * class User extends Model
 * {
 *     use Auditable;
 * }
 */
trait Auditable
{
    /**
     * Boot the Auditable trait.
     */
    public static function bootAuditable(): void
    {
        // Log when a model is created
        static::created(function ($model) {
            if (static::shouldAudit('created')) {
                app(AuditLogService::class)->logCreate($model);
            }
        });

        // Log when a model is updated
        static::updated(function ($model) {
            if (static::shouldAudit('updated')) {
                $oldValues = $model->getOriginal();
                $changedAttributes = $model->getChanges();

                // Only log if there are actual changes (excluding timestamps)
                $significantChanges = array_diff_key($changedAttributes, array_flip(['updated_at']));

                if (! empty($significantChanges)) {
                    app(AuditLogService::class)->logUpdate($model, $oldValues);
                }
            }
        });

        // Log when a model is deleted
        static::deleted(function ($model) {
            if (static::shouldAudit('deleted')) {
                app(AuditLogService::class)->logDelete($model);
            }
        });
    }

    /**
     * Determine if the action should be audited.
     */
    protected static function shouldAudit(string $action): bool
    {
        // Check if auditing is globally disabled
        if (config('audit.enabled', true) === false) {
            return false;
        }

        // Check if the model has disabled auditing
        if (property_exists(static::class, 'auditingDisabled') && static::$auditingDisabled) {
            return false;
        }

        // Check if the specific action is excluded
        if (property_exists(static::class, 'auditExclude')) {
            return ! in_array($action, static::$auditExclude);
        }

        // Check if only specific actions should be audited
        if (property_exists(static::class, 'auditInclude')) {
            return in_array($action, static::$auditInclude);
        }

        return true;
    }

    /**
     * Get the audit logs for this model.
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'entity', 'entity_type', 'entity_id');
    }

    /**
     * Get the audit logs for this model instance.
     */
    public function getAuditLogs(int $limit = 50)
    {
        return AuditLog::where('entity_type', static::class)
            ->where('entity_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Disable auditing temporarily.
     */
    public static function disableAuditing(): void
    {
        static::$auditingDisabled = true;
    }

    /**
     * Enable auditing.
     */
    public static function enableAuditing(): void
    {
        static::$auditingDisabled = false;
    }

    /**
     * Execute a callback without auditing.
     */
    public static function withoutAuditing(callable $callback)
    {
        static::disableAuditing();

        try {
            return $callback();
        } finally {
            static::enableAuditing();
        }
    }

    /**
     * Get attributes that should be hidden from audit logs.
     */
    public function getAuditHiddenAttributes(): array
    {
        if (property_exists($this, 'auditHidden')) {
            return $this->auditHidden;
        }

        return [
            'password',
            'remember_token',
            'api_token',
        ];
    }

    /**
     * Get the module name for this model.
     */
    public function getAuditModule(): ?string
    {
        if (property_exists($this, 'auditModule')) {
            return $this->auditModule;
        }

        return null;
    }
}
