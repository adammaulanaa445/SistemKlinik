<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeInput
{
    /**
     * Fields that must never be altered (beyond null-byte removal).
     */
    protected array $protected = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
    ];

    public function handle(Request $request, Closure $next)
    {
        $request->replace($this->sanitizeArray($request->all()));

        return $next($request);
    }

    protected function sanitizeArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = $this->sanitizeValue($value, (string) $key);
        }
        return $result;
    }

    protected function sanitizeValue(mixed $value, string $key): mixed
    {
        if (is_array($value)) {
            return $this->sanitizeArray($value);
        }

        if (! is_string($value)) {
            return $value;
        }

        // Always strip null bytes (injection hardening).
        $value = str_replace("\0", '', $value);

        if (in_array($key, $this->protected, true)) {
            return $value;
        }

        // Trim whitespace and strip HTML/JS tags from free-text input.
        return strip_tags(trim($value));
    }
}