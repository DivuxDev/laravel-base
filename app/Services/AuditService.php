<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    /**
     * Create an audit log entry for the current request context.
     *
     * @param  array<string, mixed>  $data
     */
    public static function log(string $action, array $data = []): AuditLog
    {
        return AuditLog::create([
            'user_id'        => $data['user_id'] ?? auth()->id(),
            'action'         => $action,
            'auditable_type' => $data['auditable_type'] ?? null,
            'auditable_id'   => $data['auditable_id'] ?? null,
            'old_values'     => $data['old_values'] ?? null,
            'new_values'     => $data['new_values'] ?? null,
            'ip_address'     => request()->ip() ?? '0.0.0.0',
            'user_agent'     => request()->userAgent(),
        ]);
    }
}
