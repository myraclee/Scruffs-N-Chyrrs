<?php

namespace Tests\Feature;

use App\Models\CustomerOrderGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DropLegacyPaymentProofColumnMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_keeps_legacy_column_when_non_empty_values_exist(): void
    {
        $this->ensureLegacyColumnExists();

        $customer = User::factory()->create();

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'approved',
            'payment_status' => 'awaiting_payment',
            'total_price' => 100,
        ]);

        DB::table('customer_order_groups')
            ->where('id', $group->id)
            ->update(['payment_proof' => 'legacy/proof.png']);

        $this->runDropMigrationUp();

        $this->assertTrue(Schema::hasColumn('customer_order_groups', 'payment_proof'));
    }

    public function test_migration_drops_legacy_column_when_values_are_empty_everywhere(): void
    {
        $this->ensureLegacyColumnExists();

        $customer = User::factory()->create();

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'approved',
            'payment_status' => 'awaiting_payment',
            'total_price' => 100,
        ]);

        DB::table('customer_order_groups')
            ->where('id', $group->id)
            ->update(['payment_proof' => '']);

        $this->runDropMigrationUp();

        $this->assertFalse(Schema::hasColumn('customer_order_groups', 'payment_proof'));
    }

    private function ensureLegacyColumnExists(): void
    {
        if (Schema::hasColumn('customer_order_groups', 'payment_proof')) {
            return;
        }

        Schema::table('customer_order_groups', function ($table): void {
            $table->string('payment_proof')->nullable();
        });
    }

    private function runDropMigrationUp(): void
    {
        $migration = require base_path('database/migrations/2026_04_13_130000_drop_legacy_payment_proof_column_when_empty.php');

        if (! is_object($migration) || ! method_exists($migration, 'up')) {
            throw new \RuntimeException('Drop-legacy-payment-proof migration does not expose an up() method.');
        }

        call_user_func([$migration, 'up']);
    }
}