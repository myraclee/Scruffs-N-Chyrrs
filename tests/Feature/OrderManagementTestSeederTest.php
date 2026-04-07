<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use App\Models\User;
use Database\Seeders\ContentInventorySeeder;
use Database\Seeders\OrderManagementTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTestSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_management_seeder_creates_balanced_status_coverage_and_varied_details(): void
    {
        $this->seed(ContentInventorySeeder::class);
        $this->seed(OrderManagementTestSeeder::class);

        $seedCustomerIds = User::query()
            ->whereIn('email', OrderManagementTestSeeder::SEEDED_CUSTOMER_EMAILS)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $groups = CustomerOrderGroup::query()
            ->with('orders')
            ->whereIn('user_id', $seedCustomerIds)
            ->orderBy('id')
            ->get();

        $this->assertCount(OrderManagementTestSeeder::SEEDED_GROUP_COUNT, $groups);

        $statusCounts = $groups->countBy('status');

        foreach (['waiting', 'approved', 'preparing', 'ready', 'completed', 'cancelled'] as $status) {
            $this->assertSame(4, (int) ($statusCounts[$status] ?? 0));
        }

        foreach ($groups as $group) {
            $this->assertGreaterThan(0, $group->orders->count());
            $this->assertTrue($group->orders->every(
                fn (CustomerOrder $order): bool => $order->status === $group->status
            ));

            $this->assertContains($group->payment_status, [
                'awaiting_payment',
                'waiting_payment_confirmation',
                'payment_received',
                'payment_cancelled',
            ]);

            if (in_array($group->status, ['preparing', 'ready', 'completed'], true)) {
                $this->assertSame('payment_received', $group->payment_status);
                $this->assertNotNull($group->payment_submitted_at);
                $this->assertNotNull($group->payment_confirmed_at);
                $this->assertNotNull($group->payment_confirmed_by);
            }

            if ($group->status === 'cancelled') {
                $this->assertSame('payment_cancelled', $group->payment_status);
                $this->assertContains($group->cancellation_reason, ['owner_declined', 'customer_cancelled']);
            }

            if ($group->payment_status === 'waiting_payment_confirmation') {
                $this->assertSame('approved', $group->status);
                $this->assertNotNull($group->payment_submitted_at);
                $this->assertNull($group->payment_confirmed_at);
            }

            $expectedTotal = round((float) $group->orders->sum(
                fn (CustomerOrder $order): float => (float) $order->total_price
            ), 2);

            $this->assertSame($expectedTotal, round((float) $group->total_price, 2));
        }

        $minCreatedAt = $groups->min('created_at');
        $maxCreatedAt = $groups->max('created_at');

        $this->assertNotNull($minCreatedAt);
        $this->assertNotNull($maxCreatedAt);
        $this->assertTrue($minCreatedAt->lt($maxCreatedAt->copy()->subDays(10)));

        $cancelledGroups = $groups->where('status', 'cancelled');
        $this->assertGreaterThan(0, $cancelledGroups->whereNotNull('inventory_restored_at')->count());
        $this->assertGreaterThan(0, $cancelledGroups->whereNull('inventory_restored_at')->count());

        $paymentStatusCounts = $groups->countBy('payment_status');
        $this->assertGreaterThan(0, (int) ($paymentStatusCounts['awaiting_payment'] ?? 0));
        $this->assertGreaterThan(0, (int) ($paymentStatusCounts['waiting_payment_confirmation'] ?? 0));
        $this->assertGreaterThan(0, (int) ($paymentStatusCounts['payment_received'] ?? 0));
        $this->assertGreaterThan(0, (int) ($paymentStatusCounts['payment_cancelled'] ?? 0));
    }

    public function test_order_management_seeder_is_idempotent_for_seeded_users(): void
    {
        $this->seed(ContentInventorySeeder::class);
        $this->seed(OrderManagementTestSeeder::class);

        $seedCustomerIds = User::query()
            ->whereIn('email', OrderManagementTestSeeder::SEEDED_CUSTOMER_EMAILS)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $firstGroupCount = CustomerOrderGroup::query()
            ->whereIn('user_id', $seedCustomerIds)
            ->count();

        $firstOrderCount = CustomerOrder::query()
            ->whereIn('user_id', $seedCustomerIds)
            ->count();

        $this->seed(OrderManagementTestSeeder::class);

        $secondGroupCount = CustomerOrderGroup::query()
            ->whereIn('user_id', $seedCustomerIds)
            ->count();

        $secondOrderCount = CustomerOrder::query()
            ->whereIn('user_id', $seedCustomerIds)
            ->count();

        $this->assertSame($firstGroupCount, $secondGroupCount);
        $this->assertSame($firstOrderCount, $secondOrderCount);
        $this->assertSame(OrderManagementTestSeeder::SEEDED_GROUP_COUNT, $secondGroupCount);
    }
}
