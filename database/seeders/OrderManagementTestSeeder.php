<?php

namespace Database\Seeders;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use App\Models\OrderTemplate;
use App\Models\RushFee;
use App\Models\User;
use App\Services\InventoryStockService;
use App\Services\OrderPricingService;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class OrderManagementTestSeeder extends Seeder
{
    public const SEEDED_OWNER_EMAIL = 'owner@gmail.com';

    /**
     * @var list<string>
     */
    public const SEEDED_CUSTOMER_EMAILS = [
        'customer1@gmail.com',
        'customer2@gmail.com',
        'customer3@gmail.com',
        'customer4@gmail.com',
        'customer5@gmail.com',
        'customer6@gmail.com',
    ];

    public const SEEDED_GROUP_COUNT = 24;

    /**
     * @var list<string>
     */
    private const SEEDED_PRODUCT_NAMES = [
        'Kiss-Cut Stickers',
        'Die-Cut Stickers',
        'Button Pins',
        'Photocards',
        'Business Cards',
        'Posters - 3R',
        'Posters - 4R',
        'Posters - 5R',
        'Posters - A4',
        'Photo-Paper Prints',
    ];

    /**
     * @var list<string>
     */
    private const STATUSES = [
        'waiting',
        'approved',
        'preparing',
        'ready',
        'completed',
        'cancelled',
    ];

    public function run(): void
    {
        $this->command?->info('Seeding order-management test groups...');

        $pricingService = app(OrderPricingService::class);
        $inventoryService = app(InventoryStockService::class);

        $customers = $this->seedUsers();
        $customerIds = $customers->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $this->cleanupExistingSeededOrders($customerIds);

        $templates = $this->loadTemplatesForSeededCatalog();
        $rushFees = $this->loadRushFees();

        $seedNow = Carbon::now();

        for ($groupIndex = 0; $groupIndex < self::SEEDED_GROUP_COUNT; $groupIndex++) {
            $status = self::STATUSES[$groupIndex % count(self::STATUSES)];
            $customer = $customers[$groupIndex % $customers->count()];
            $createdAt = $seedNow
                ->copy()
                ->subDays(self::SEEDED_GROUP_COUNT - $groupIndex)
                ->setTime(9 + ($groupIndex % 8), 15);
            $updatedAt = $createdAt->copy()->addHours($this->resolveStatusOffsetHours($status));

            $lineCount = ($groupIndex % 3) + 1;
            $ordersPayload = [];
            $inventoryLines = [];

            for ($lineIndex = 0; $lineIndex < $lineCount; $lineIndex++) {
                /** @var OrderTemplate $template */
                $template = $templates[(($groupIndex * 2) + $lineIndex) % $templates->count()];

                $selectedOptions = $this->buildSelectedOptions($template, $groupIndex, $lineIndex);
                $quantity = $this->resolveQuantity($template, $groupIndex, $lineIndex);
                $rushFeeId = $this->resolveRushFeeId($rushFees, $groupIndex, $lineIndex);
                $specialInstructions = $this->resolveSpecialInstructions($groupIndex, $lineIndex);

                $pricing = $pricingService->calculate(
                    $template,
                    $selectedOptions,
                    $quantity,
                    $rushFeeId,
                    $specialInstructions
                );

                if (!($pricing['success'] ?? false)) {
                    throw new RuntimeException(
                        sprintf(
                            'Unable to calculate seeded pricing for template %d (group %d line %d): %s',
                            $template->id,
                            $groupIndex + 1,
                            $lineIndex + 1,
                            $pricing['message'] ?? 'unknown pricing error'
                        )
                    );
                }

                $ordersPayload[] = [
                    'user_id' => (int) $customer->id,
                    'product_id' => (int) $template->product_id,
                    'order_template_id' => (int) $template->id,
                    'rush_fee_id' => $rushFeeId,
                    'selected_options' => $selectedOptions,
                    'quantity' => $quantity,
                    'special_instructions' => $specialInstructions,
                    'base_price' => (float) $pricing['base_price'],
                    'discount_amount' => (float) $pricing['discount_amount'],
                    'rush_fee_amount' => (float) $pricing['rush_fee_amount'],
                    'layout_fee_amount' => (float) $pricing['layout_fee_amount'],
                    'total_price' => (float) $pricing['total_price'],
                    'status' => $status,
                ];

                $inventoryLines[] = [
                    'product_id' => (int) $template->product_id,
                    'quantity' => $quantity,
                    'selected_options' => $selectedOptions,
                ];
            }

            $requirements = $inventoryService->calculateRequirements($inventoryLines);
            [$inventoryDeductedAt, $inventoryRestoredAt] = $this->resolveInventoryTimestamps(
                $status,
                $groupIndex,
                $createdAt
            );

            $group = new CustomerOrderGroup([
                'user_id' => (int) $customer->id,
                'status' => $status,
                'general_drive_link' => $this->resolveDriveLink($groupIndex),
                'subtotal_price' => round((float) collect($ordersPayload)->sum('base_price'), 2),
                'discount_total' => round((float) collect($ordersPayload)->sum('discount_amount'), 2),
                'rush_fee_total' => round((float) collect($ordersPayload)->sum('rush_fee_amount'), 2),
                'layout_fee_total' => round((float) collect($ordersPayload)->sum('layout_fee_amount'), 2),
                'total_price' => round((float) collect($ordersPayload)->sum('total_price'), 2),
                'inventory_material_requirements' => $requirements,
                'inventory_deducted_at' => $inventoryDeductedAt,
                'inventory_restored_at' => $inventoryRestoredAt,
            ]);

            $group->created_at = $createdAt;
            $group->updated_at = $updatedAt;
            $group->save();

            foreach ($ordersPayload as $lineIndex => $orderPayload) {
                $order = new CustomerOrder([
                    'customer_order_group_id' => (int) $group->id,
                    'user_id' => $orderPayload['user_id'],
                    'product_id' => $orderPayload['product_id'],
                    'order_template_id' => $orderPayload['order_template_id'],
                    'rush_fee_id' => $orderPayload['rush_fee_id'],
                    'selected_options' => $orderPayload['selected_options'],
                    'quantity' => $orderPayload['quantity'],
                    'special_instructions' => $orderPayload['special_instructions'],
                    'base_price' => $orderPayload['base_price'],
                    'discount_amount' => $orderPayload['discount_amount'],
                    'rush_fee_amount' => $orderPayload['rush_fee_amount'],
                    'layout_fee_amount' => $orderPayload['layout_fee_amount'],
                    'total_price' => $orderPayload['total_price'],
                    'status' => $orderPayload['status'],
                ]);

                $order->created_at = $createdAt->copy()->addMinutes(($lineIndex + 1) * 10);
                $order->updated_at = $updatedAt;
                $order->save();
            }
        }

        $this->command?->info('Order-management test seeding completed.');
    }

    /**
     * @return Collection<int, User>
     */
    private function seedUsers(): Collection
    {
        User::query()->updateOrCreate(
            ['email' => self::SEEDED_OWNER_EMAIL],
            [
                'first_name' => 'Seed',
                'last_name' => 'Owner',
                'contact_number' => '9170000001',
                'password' => Hash::make('password'),
                'user_type' => 'owner',
                'email_verified_at' => now(),
                'login_attempts' => 0,
                'is_locked' => false,
                'must_reset_password' => false,
                'password_reset_completed_at' => null,
                'lockout_until' => null,
            ]
        );

        $customers = collect();

        foreach (self::SEEDED_CUSTOMER_EMAILS as $index => $email) {
            $customer = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => 'Seed',
                    'last_name' => 'Customer '.($index + 1),
                    'contact_number' => sprintf('9170000%03d', $index + 2),
                    'password' => Hash::make('password'),
                    'user_type' => 'customer',
                    'email_verified_at' => now(),
                    'login_attempts' => 0,
                    'is_locked' => false,
                    'must_reset_password' => false,
                    'password_reset_completed_at' => null,
                    'lockout_until' => null,
                ]
            );

            $customers->push($customer);
        }

        return $customers;
    }

    /**
     * @param array<int, int> $customerIds
     */
    private function cleanupExistingSeededOrders(array $customerIds): void
    {
        if (empty($customerIds)) {
            return;
        }

        CustomerOrder::query()
            ->whereIn('user_id', $customerIds)
            ->delete();

        CustomerOrderGroup::query()
            ->whereIn('user_id', $customerIds)
            ->delete();
    }

    /**
     * @return Collection<int, OrderTemplate>
     */
    private function loadTemplatesForSeededCatalog(): Collection
    {
        $templates = OrderTemplate::query()
            ->with([
                'product:id,name',
                'options.optionTypes',
                'options',
                'pricings',
                'discounts',
                'minOrder',
                'layoutFee',
            ])
            ->whereHas('product', function ($query): void {
                $query->whereIn('name', self::SEEDED_PRODUCT_NAMES);
            })
            ->get()
            ->values();

        if ($templates->isEmpty()) {
            throw new RuntimeException(
                'No seeded order templates were found. Run ContentInventorySeeder first.'
            );
        }

        $availableNames = $templates
            ->pluck('product.name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $missingNames = array_values(array_diff(self::SEEDED_PRODUCT_NAMES, $availableNames));

        if (!empty($missingNames)) {
            throw new RuntimeException(
                'Order templates are missing for products: '.implode(', ', $missingNames).
                ' Run ContentInventorySeeder first.'
            );
        }

        return $templates;
    }

    /**
     * @return Collection<int, RushFee>
     */
    private function loadRushFees(): Collection
    {
        $rushFees = RushFee::query()
            ->with(['timeframes' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->ordered()
            ->get()
            ->values();

        if ($rushFees->isEmpty()) {
            throw new RuntimeException('Rush fee data is missing. Run ContentInventorySeeder first.');
        }

        return $rushFees;
    }

    /**
     * @return array<string, int>
     */
    private function buildSelectedOptions(OrderTemplate $template, int $groupIndex, int $lineIndex): array
    {
        $selected = [];

        $orderedOptions = $template->options
            ->sortBy('position')
            ->values();

        foreach ($orderedOptions as $optionIndex => $option) {
            $availableTypes = $option->optionTypes
                ->where('is_available', true)
                ->sortBy('position')
                ->values();

            if ($availableTypes->isEmpty()) {
                throw new RuntimeException(
                    "Order template {$template->id} option {$option->label} has no available option types."
                );
            }

            $pickIndex = ($groupIndex + $lineIndex + $optionIndex) % $availableTypes->count();
            $pickedType = $availableTypes[$pickIndex];

            $selected[(string) $option->id] = (int) $pickedType->id;
        }

        return $selected;
    }

    private function resolveQuantity(OrderTemplate $template, int $groupIndex, int $lineIndex): int
    {
        $minimumQuantity = max(1, (int) ($template->minOrder?->min_quantity ?? 1));

        $topDiscountMinQuantity = (int) ($template->discounts
            ->sortByDesc('min_quantity')
            ->first()?->min_quantity ?? 0);

        if ($topDiscountMinQuantity > 0 && (($groupIndex + $lineIndex) % 2) === 0) {
            return max($minimumQuantity, $topDiscountMinQuantity);
        }

        $step = $minimumQuantity >= 50
            ? 10
            : ($minimumQuantity >= 10 ? 2 : 1);

        return $minimumQuantity + ((($groupIndex + $lineIndex) % 3) * $step);
    }

    private function resolveRushFeeId(Collection $rushFees, int $groupIndex, int $lineIndex): ?int
    {
        if ((($groupIndex + $lineIndex) % 2) !== 0) {
            return null;
        }

        /** @var RushFee $rushFee */
        $rushFee = $rushFees[($groupIndex + $lineIndex) % $rushFees->count()];

        return (int) $rushFee->id;
    }

    private function resolveSpecialInstructions(int $groupIndex, int $lineIndex): ?string
    {
        $variant = ($groupIndex + $lineIndex) % 3;

        if ($variant === 0) {
            return 'front-artwork.png,back-artwork.png';
        }

        if ($variant === 1) {
            return 'single-layout.png';
        }

        return null;
    }

    private function resolveDriveLink(int $groupIndex): ?string
    {
        if (($groupIndex % 5) === 0) {
            return null;
        }

        return sprintf(
            'https://drive.google.com/drive/folders/seed-order-group-%02d',
            $groupIndex + 1
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon|null}
     */
    private function resolveInventoryTimestamps(
        string $status,
        int $groupIndex,
        Carbon $createdAt
    ): array {
        $deductedAt = $createdAt->copy()->addHour();
        $restoredAt = null;

        $statusCycleIndex = intdiv($groupIndex, count(self::STATUSES));

        if ($status === 'cancelled' && ($statusCycleIndex % 2) === 0) {
            $restoredAt = $createdAt->copy()->addHours(4);
        }

        return [$deductedAt, $restoredAt];
    }

    private function resolveStatusOffsetHours(string $status): int
    {
        return match ($status) {
            'waiting' => 1,
            'approved' => 3,
            'preparing' => 6,
            'ready' => 10,
            'completed' => 18,
            'cancelled' => 8,
            default => 1,
        };
    }
}
