<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an audit event
     * 
     * @param string $action The action performed
     * @param string $entityType The type of entity affected
     * @param int $targetId The ID of the entity affected
     * @param array $oldValues Old values before change
     * @param array $newValues New values after change
     * @return AuditLog
     */
    public static function log($action, $entityType, $targetId, $oldValues = null, $newValues = null)
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'target_id' => $targetId,
            'action' => $action,
            'entity_type' => $entityType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent()
        ]);
    }
    
    /**
     * Log user status change
     * 
     * @param int $userId The user ID
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @return AuditLog
     */
    public static function logUserStatusChange($userId, $oldStatus, $newStatus)
    {
        return self::log(
            'status_change',
            'User',
            $userId,
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );
    }
}