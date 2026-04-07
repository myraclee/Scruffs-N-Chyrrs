<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Throwable;

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
     * Detect checkout schema drift when payment lifecycle columns were not migrated
     * on customer_order_groups.
     */
    public static function isMissingCustomerOrderGroupPaymentColumns(QueryException $exception): bool
    {
        $sourceMessage = $exception->getPrevious()?->getMessage() ?? $exception->getMessage();
        return self::matchesMissingCustomerOrderGroupPaymentColumnsMessage($sourceMessage);
    }

    public static function isMissingCustomerOrderGroupPaymentColumnsFromThrowable(Throwable $exception): bool
    {
        return self::matchesMissingCustomerOrderGroupPaymentColumnsMessage($exception->getMessage());
    }

    private static function matchesMissingCustomerOrderGroupPaymentColumnsMessage(string $sourceMessage): bool
    {
        $message = strtolower($sourceMessage);

        $looksLikeMissingColumn = str_contains($message, 'unknown column')
            || str_contains($message, 'no such column')
            || str_contains($message, 'column not found');

        if (! $looksLikeMissingColumn) {
            return false;
        }

        $isCustomerOrderGroups = str_contains($message, 'customer_order_groups')
            || str_contains($message, 'insert into "customer_order_groups"')
            || str_contains($message, 'insert into customer_order_groups');
        $isKnownPaymentColumn = str_contains($message, 'payment_status')
            || str_contains($message, 'cancellation_reason')
            || str_contains($message, 'payment_method')
            || str_contains($message, 'payment_reference_number')
            || str_contains($message, 'payment_proof_path')
            || str_contains($message, 'payment_submitted_at')
            || str_contains($message, 'payment_confirmed_at')
            || str_contains($message, 'payment_confirmed_by')
            || str_contains($message, 'payment_confirmation_note');

        return ($isCustomerOrderGroups && $isKnownPaymentColumn) || $isKnownPaymentColumn;
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
