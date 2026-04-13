<?php

namespace Tests\Feature;

use App\Models\CustomerOrderGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyOrderControllerPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_submit_payment_persists_canonical_fields(): void
    {
        Storage::fake('public');

        $customer = User::factory()->create();

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'approved',
            'payment_status' => 'awaiting_payment',
            'total_price' => 100,
        ]);

        $response = $this->actingAs($customer)
            ->post("/customer/orders/{$group->id}/pay", [
                'payment_proof' => $this->fakePngUpload('proof.png'),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $group->refresh();

        $this->assertSame('waiting_payment_confirmation', $group->payment_status);
        $this->assertNotNull($group->payment_proof_path);
        $this->assertNotNull($group->payment_submitted_at);

        $this->assertTrue(Storage::disk('public')->exists((string) $group->payment_proof_path));
    }

    public function test_legacy_submit_payment_rejects_unexpected_order_state(): void
    {
        Storage::fake('public');

        $customer = User::factory()->create();

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'waiting',
            'payment_status' => 'awaiting_payment',
            'total_price' => 100,
        ]);

        $response = $this->actingAs($customer)
            ->post("/customer/orders/{$group->id}/pay", [
                'payment_proof' => $this->fakePngUpload('proof.png'),
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $group->refresh();

        $this->assertSame('awaiting_payment', $group->payment_status);
        $this->assertNull($group->payment_proof_path);
    }

    public function test_legacy_submit_payment_rejects_order_groups_not_owned_by_user(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $otherCustomer = User::factory()->create();

        $group = CustomerOrderGroup::create([
            'user_id' => $owner->id,
            'status' => 'approved',
            'payment_status' => 'awaiting_payment',
            'total_price' => 100,
        ]);

        $response = $this->actingAs($otherCustomer)
            ->post("/customer/orders/{$group->id}/pay", [
                'payment_proof' => $this->fakePngUpload('proof.png'),
            ]);

        $response
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_admin_payment_status_route_updates_canonical_status(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $customer = User::factory()->create();

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'approved',
            'payment_status' => 'awaiting_payment',
            'total_price' => 100,
        ]);

        $response = $this->actingAs($owner)
            ->patchJson("/admin/orders/{$group->id}/payment-status", [
                'payment_status' => 'payment_cancelled',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_status', 'payment_cancelled');

        $this->assertDatabaseHas('customer_order_groups', [
            'id' => $group->id,
            'payment_status' => 'payment_cancelled',
        ]);
    }

    public function test_admin_payment_status_route_rejects_non_canonical_status(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $customer = User::factory()->create();

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'approved',
            'payment_status' => 'awaiting_payment',
            'total_price' => 100,
        ]);

        $response = $this->actingAs($owner)
            ->patchJson("/admin/orders/{$group->id}/payment-status", [
                'payment_status' => 'Awaiting Payment',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_status']);
    }

    private function fakePngUpload(string $filename = 'proof.png'): UploadedFile
    {
        $tinyPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7f3zQAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($filename, $tinyPng ?: 'png');
    }
}