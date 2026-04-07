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
