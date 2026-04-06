<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GoogleDriveUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid Google Drive URL.');
            return;
        }

        $normalized = self::normalize($value);

        if ($normalized === null || ! self::isValid($normalized)) {
            $fail('The :attribute must be a valid Google Drive URL.');
        }
    }

    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    public static function isValid(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return false;
        }

        if (($parts['scheme'] ?? null) !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host !== 'drive.google.com') {
            return false;
        }

        $path = (string) ($parts['path'] ?? '/');
        $path = $path === '' ? '/' : rtrim($path, '/');

        if ($path === '/' || $path === '/drive' || str_starts_with($path, '/drive/')) {
            return true;
        }

        if (preg_match('#^/drive/folders/[A-Za-z0-9_-]+$#', $path) === 1) {
            return true;
        }

        if (preg_match('#^/file/d/[A-Za-z0-9_-]+(?:/.*)?$#', $path) === 1) {
            return true;
        }

        parse_str((string) ($parts['query'] ?? ''), $queryParams);
        $id = $queryParams['id'] ?? null;
        $isValidId = is_string($id) && preg_match('/^[A-Za-z0-9_-]+$/', $id) === 1;

        if ($path === '/open' && $isValidId) {
            return true;
        }

        if ($path === '/uc' && $isValidId) {
            return true;
        }

        return false;
    }
}
