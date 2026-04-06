<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use App\Models\Material;
use App\Models\OrderTemplate;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OwnerDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_owner_dashboard_metrics_api_returns_expected_weekly_and_chart_data(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 7, 10, 0, 0));

        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $customer = User::factory()->create();

        [$stickers, $stickersTemplate] = $this->createProductWithTemplate('Stickers');
        [$posters, $postersTemplate] = $this->createProductWithTemplate('Posters');

        Material::create([
            'name' => 'Glossy Lamination',
            'units' => 2,
            'low_stock_threshold' => 5,
        ]);

        Material::create([
            'name' => 'Cardstock',
            'units' => 0,
            'low_stock_threshold' => 5,
        ]);

        Material::create([
            'name' => 'Ink',
            'units' => 25,
            'low_stock_threshold' => 5,
        ]);

        // Current week (Mon-Sun) fixtures
        $this->createGroupedOrder(
            customer: $customer,
            product: $stickers,
            template: $stickersTemplate,
            status: 'waiting',
            totalPrice: 100,
            quantity: 2,
            createdAt: CarbonImmutable::parse('2026-04-07 09:00:00'),
        );

        $this->createGroupedOrder(
            customer: $customer,
            product: $posters,
            template: $postersTemplate,
            status: 'approved',
            totalPrice: 80,
            quantity: 3,
            createdAt: CarbonImmutable::parse('2026-04-08 09:00:00'),
        );

        $this->createGroupedOrder(
            customer: $customer,
            product: $stickers,
            template: $stickersTemplate,
            status: 'completed',
            totalPrice: 200,
            quantity: 4,
            createdAt: CarbonImmutable::parse('2026-04-09 09:00:00'),
        );

        $this->createGroupedOrder(
            customer: $customer,
            product: $posters,
            template: $postersTemplate,
            status: 'cancelled',
            totalPrice: 300,
            quantity: 9,
            createdAt: CarbonImmutable::parse('2026-04-10 09:00:00'),
        );

        // January fixtures for chart checks
        $this->createGroupedOrder(
            customer: $customer,
            product: $stickers,
            template: $stickersTemplate,
            status: 'completed',
            totalPrice: 150,
            quantity: 5,
            createdAt: CarbonImmutable::parse('2026-01-15 12:00:00'),
        );

        $this->createGroupedOrder(
            customer: $customer,
            product: $posters,
            template: $postersTemplate,
            status: 'cancelled',
            totalPrice: 999,
            quantity: 20,
            createdAt: CarbonImmutable::parse('2026-01-21 12:00:00'),
        );

        $response = $this
            ->actingAs($owner)
            ->getJson('/api/owner/dashboard/metrics?year=2026&month=3');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.selected_year', 2026)
            ->assertJsonPath('data.selected_month', 3)
            ->assertJsonPath('data.weekly_report.total_sales', 380)
            ->assertJsonPath('data.weekly_report.items_sold', 9)
            ->assertJsonPath('data.weekly_report.low_stock_item_name', 'Cardstock')
            ->assertJsonPath('data.weekly_sales.total_orders', 4)
            ->assertJsonPath('data.weekly_sales.received_payment', 1)
            ->assertJsonPath('data.weekly_sales.pending_payment', 2)
            ->assertJsonPath('data.weekly_sales.canceled_orders', 1)
            ->assertJsonPath('data.charts.monthly_revenue.0', 150)
            ->assertJsonPath('data.charts.monthly_revenue.3', 380)
            ->assertJsonPath('data.charts.monthly_sales.has_data_by_month.1', false)
            ->assertJsonPath('data.charts.monthly_sales.labels_by_month.1.0', 'No data')
            ->assertJsonPath('data.charts.monthly_sales.values_by_month.1.0', 0)
            ->assertJsonPath('data.charts.monthly_sales.labels_by_month.3.0', 'Stickers')
            ->assertJsonPath('data.charts.monthly_sales.values_by_month.3.0', 6)
            ->assertJsonPath('data.charts.monthly_sales.labels_by_month.3.1', 'Posters')
            ->assertJsonPath('data.charts.monthly_sales.values_by_month.3.1', 3);
    }

    public function test_owner_dashboard_metrics_api_includes_archived_deleted_account_contributions(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 7, 10, 0, 0));

        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $customer = User::factory()->create();

        [$liveProduct, $liveTemplate] = $this->createProductWithTemplate('Live Product');
        [$archivedProduct] = $this->createProductWithTemplate('Archived Product');

        $this->createGroupedOrder(
            customer: $customer,
            product: $liveProduct,
            template: $liveTemplate,
            status: 'completed',
            totalPrice: 100,
            quantity: 2,
            createdAt: CarbonImmutable::parse('2026-04-07 09:00:00'),
        );

        DB::table('dashboard_deleted_account_daily_metrics')->insert([
            'metric_date' => '2026-04-07',
            'total_sales' => 55,
            'items_sold' => 3,
            'total_orders' => 2,
            'received_payment' => 1,
            'pending_payment' => 1,
            'canceled_orders' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('dashboard_deleted_account_monthly_product_sales')->insert([
            'year' => 2026,
            'month' => 4,
            'product_id' => $archivedProduct->id,
            'product_name' => $archivedProduct->name,
            'total_quantity' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($owner)
            ->getJson('/api/owner/dashboard/metrics?year=2026&month=3');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.weekly_report.total_sales', 155)
            ->assertJsonPath('data.weekly_report.items_sold', 5)
            ->assertJsonPath('data.weekly_sales.total_orders', 3)
            ->assertJsonPath('data.weekly_sales.received_payment', 2)
            ->assertJsonPath('data.weekly_sales.pending_payment', 1)
            ->assertJsonPath('data.weekly_sales.canceled_orders', 0)
            ->assertJsonPath('data.charts.monthly_revenue.3', 155)
            ->assertJsonPath('data.charts.monthly_sales.labels_by_month.3.0', 'Archived Product')
            ->assertJsonPath('data.charts.monthly_sales.values_by_month.3.0', 4)
            ->assertJsonPath('data.charts.monthly_sales.labels_by_month.3.1', 'Live Product')
            ->assertJsonPath('data.charts.monthly_sales.values_by_month.3.1', 2);
    }

    public function test_non_owner_user_is_forbidden_from_dashboard_metrics_api(): void
    {
        $customer = User::factory()->create([
            'user_type' => 'customer',
        ]);

        $response = $this
            ->actingAs($customer)
            ->getJson('/api/owner/dashboard/metrics');

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized access.');
    }

    /**
     * @return array{0: Product, 1: OrderTemplate}
     */
    private function createProductWithTemplate(string $productName): array
    {
        $product = Product::create([
            'name' => $productName,
            'slug' => Str::slug($productName).'-'.Str::lower(Str::random(6)),
            'description' => null,
            'cover_image_path' => null,
        ]);

        $template = OrderTemplate::create([
            'product_id' => $product->id,
        ]);

        return [$product, $template];
    }

    private function createGroupedOrder(
        User $customer,
        Product $product,
        OrderTemplate $template,
        string $status,
        float $totalPrice,
        int $quantity,
        CarbonImmutable $createdAt,
    ): void {
        $group = new CustomerOrderGroup([
            'user_id' => $customer->id,
            'status' => $status,
            'subtotal_price' => $totalPrice,
            'discount_total' => 0,
            'rush_fee_total' => 0,
            'layout_fee_total' => 0,
            'total_price' => $totalPrice,
        ]);

        $groupTimestamp = Carbon::instance($createdAt);
        $group->created_at = $groupTimestamp;
        $group->updated_at = $groupTimestamp;
        $group->save();

        $order = new CustomerOrder([
            'customer_order_group_id' => $group->id,
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'order_template_id' => $template->id,
            'selected_options' => [],
            'quantity' => $quantity,
            'base_price' => $totalPrice,
            'discount_amount' => 0,
            'rush_fee_amount' => 0,
            'layout_fee_amount' => 0,
            'total_price' => $totalPrice,
            'status' => $status,
        ]);

        $orderTimestamp = Carbon::instance($createdAt);
        $order->created_at = $orderTimestamp;
        $order->updated_at = $orderTimestamp;
        $order->save();
    }
}
