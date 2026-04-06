<?php

namespace Tests\Unit;

use App\Support\SchemaMismatchDetector;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\TestCase;

class SchemaMismatchDetectorTest extends TestCase
{
    public function test_detects_mysql_missing_deleted_at_column_message(): void
    {
        $exception = $this->makeQueryException(
            "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'order_templates.deleted_at' in 'where clause'"
        );

        $this->assertTrue(SchemaMismatchDetector::isMissingOrderTemplateDeletedAt($exception));
    }

    public function test_detects_sqlite_missing_deleted_at_column_message(): void
    {
        $exception = $this->makeQueryException(
            'SQLSTATE[HY000]: General error: 1 no such column: order_templates.deleted_at'
        );

        $this->assertTrue(SchemaMismatchDetector::isMissingOrderTemplateDeletedAt($exception));
    }

    public function test_ignores_unrelated_query_exception_message(): void
    {
        $exception = $this->makeQueryException(
            "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'products.price' in 'field list'"
        );

        $this->assertFalse(SchemaMismatchDetector::isMissingOrderTemplateDeletedAt($exception));
    }

    public function test_build_payload_returns_strict_contract(): void
    {
        $payload = SchemaMismatchDetector::buildPayload('Failed to fetch products due to database schema mismatch.');

        $this->assertSame(false, $payload['success']);
        $this->assertSame('schema_mismatch', $payload['error_code']);
        $this->assertSame(
            'Database schema is out of date. Run "php artisan migrate" and retry.',
            $payload['hint']
        );
    }

    private function makeQueryException(string $message): QueryException
    {
        return new QueryException(
            'mysql',
            'select * from `order_templates` where `order_templates`.`deleted_at` is null',
            [],
            new \Exception($message)
        );
    }
}
