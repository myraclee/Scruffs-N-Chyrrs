<?php

namespace Tests\Feature;

use App\Models\CustomerCart;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use App\Models\Order;
use App\Models\OrderTemplate;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeleteAccountFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_delete_account(): void
    {
        $response = $this->post(route('delete-account.destroy'), [
            'current_password' => 'anything',
            'new_password_confirmation' => 'anything',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_owner_account_deletion_is_blocked(): void
    {
        $password = 'OwnerPassword123!';
        $owner = User::factory()->create([
            'user_type' => 'owner',
            'password' => Hash::make($password),
        ]);

        $response = $this
            ->actingAs($owner)
            ->from(route('delete-account'))
            ->post(route('delete-account.destroy'), $this->deletionPayload($password));

        $response
            ->assertRedirect(route('delete-account'))
            ->assertSessionHasErrors(['account_deletion']);

        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    public function test_account_deletion_fails_with_incorrect_current_password(): void
    {
        $password = 'CorrectPassword123!';
        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('delete-account'))
            ->post(route('delete-account.destroy'), $this->deletionPayload('WrongPassword123!'));

        $response
            ->assertRedirect(route('delete-account'))
            ->assertSessionHasErrors(['current_password']);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_account_deletion_is_blocked_when_grouped_orders_are_unfinished(): void
    {
        $password = 'Password123!';
        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        CustomerOrderGroup::create([
            'user_id' => $user->id,
            'status' => 'waiting',
            'total_price' => 100,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('delete-account'))
            ->post(route('delete-account.destroy'), $this->deletionPayload($password));

        $response
            ->assertRedirect(route('delete-account'))
            ->assertSessionHasErrors(['account_deletion']);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_account_deletion_is_blocked_when_legacy_orders_are_unfinished(): void
    {
        $password = 'Password123!';
        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'general_gdrive_link' => 'https://drive.google.com/legacy-order',
            'rush_fee' => 0,
            'grand_total' => 120,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('delete-account'))
            ->post(route('delete-account.destroy'), $this->deletionPayload($password));

        $response
            ->assertRedirect(route('delete-account'))
            ->assertSessionHasErrors(['account_deletion']);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_account_is_hard_deleted_when_orders_are_only_completed_or_cancelled(): void
    {
        $password = 'DeleteMe123!';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'user_type' => 'customer',
        ]);

        ['product' => $product, 'template' => $template] = $this->createProductAndTemplate();

        $completedGroup = CustomerOrderGroup::create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total_price' => 50,
        ]);

        CustomerOrderGroup::create([
            'user_id' => $user->id,
            'status' => 'cancelled',
            'total_price' => 25,
        ]);

        CustomerOrder::create([
            'customer_order_group_id' => $completedGroup->id,
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_template_id' => $template->id,
            'selected_options' => [],
            'quantity' => 1,
            'special_instructions' => null,
            'base_price' => 50,
            'discount_amount' => 0,
            'rush_fee_amount' => 0,
            'layout_fee_amount' => 0,
            'total_price' => 50,
            'status' => 'completed',
        ]);

        Order::create([
            'user_id' => $user->id,
            'status' => 'completed',
            'general_gdrive_link' => 'https://drive.google.com/completed-legacy-order',
            'rush_fee' => 0,
            'grand_total' => 75,
        ]);

        CustomerCart::create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        DB::table('sessions')->insert([
            'id' => 'delete-account-test-session-'.$user->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest Test Runner',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'token-'.$user->id,
            'code' => '123456',
            'created_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('delete-account'))
            ->post(route('delete-account.destroy'), $this->deletionPayload($password));

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Your account has been permanently deleted.');

        $this->assertGuest();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('customer_order_groups', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('customer_orders', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('orders', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('customer_carts', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    /**
     * @return array{product: Product, template: OrderTemplate}
     */
    private function createProductAndTemplate(): array
    {
        $product = Product::create([
            'name' => 'Delete Account Product '.uniqid(),
            'description' => 'Temporary product for account deletion tests.',
            'cover_image_path' => 'products/test-cover.png',
        ]);

        $template = OrderTemplate::create([
            'product_id' => $product->id,
        ]);

        return [
            'product' => $product,
            'template' => $template,
        ];
    }

    /**
     * @return array{current_password: string, new_password_confirmation: string}
     */
    private function deletionPayload(string $password): array
    {
        return [
            'current_password' => $password,
            'new_password_confirmation' => $password,
        ];
    }
}
