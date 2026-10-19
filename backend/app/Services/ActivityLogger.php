<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function log($user, string $action, string $module, ?string $description = null, $subject = null, array $metadata = []): void
    {
        try {
            ActivityLog::create([
                'user_id'     => $user?->id,
                'action'      => $action,
                'module'      => $module,
                'description' => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id'  => $subject?->id,
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
                'metadata'    => $metadata ?: null,
            ]);
        } catch (\Throwable $e) {
            // Logging should not break main flow
        }
    }
}