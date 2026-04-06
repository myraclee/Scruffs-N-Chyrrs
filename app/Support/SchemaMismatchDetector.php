<?php

namespace App\Support;

use Illuminate\Database\QueryException;

class SchemaMismatchDetector
{
    public const ERROR_CODE = 'schema_mismatch';

    /**
     * Detect the known local-dev failure where SoftDeletes expects
     * order_templates.deleted_at but the column is missing.
     */
    public static function isMissingOrderTemplateDeletedAt(QueryException $exception): bool
    {
        $sourceMessage = $exception->getPrevious()?->getMessage() ?? $exception->getMessage();
        $message = strtolower($sourceMessage);

        $looksLikeMissingColumn = str_contains($message, 'unknown column')
            || str_contains($message, 'no such column')
            || str_contains($message, 'column not found');

        if (! $looksLikeMissingColumn) {
            return false;
        }

        return str_contains($message, 'order_templates.deleted_at')
            || (str_contains($message, 'order_templates') && str_contains($message, 'deleted_at'));
    }

    /**
     * Build a strict, actionable API payload for schema mismatch failures.
     *
     * @return array{success:bool,message:string,error_code:string,hint:string}
     */
    public static function buildPayload(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'error_code' => self::ERROR_CODE,
            'hint' => 'Database schema is out of date. Run "php artisan migrate" and retry.',
        ];
    }
}
