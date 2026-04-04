<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageCategoriesEditModeContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_categories_inputs_are_disabled_in_default_modal_markup(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->get('/owner/pages/content_management');

        $response->assertOk();

        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/id="categoryNameInput"[^>]*disabled/', $html);
        $this->assertMatchesRegularExpression('/id="sortOrderInput"[^>]*disabled/', $html);
        $this->assertStringContainsString('id="enterCreateModeBtn"', $html);
    }

    public function test_manage_categories_script_contains_explicit_create_and_edit_mode_toggles(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/content_page/manage_categories_modal.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('let formMode = "idle";', $script);
        $this->assertStringContainsString('createModeBtn?.addEventListener("click", enterCreateMode);', $script);
        $this->assertStringContainsString('function enterCreateMode()', $script);
        $this->assertStringContainsString('formMode = "create";', $script);
        $this->assertStringContainsString('function setInputsEnabled(enabled)', $script);
        $this->assertStringContainsString('setInputsEnabled(false);', $script);
        $this->assertStringContainsString('setInputsEnabled(true);', $script);
        $this->assertStringContainsString('if (formMode === "idle")', $script);
        $this->assertStringContainsString('Choose Create New or Edit an existing category first.', $script);
        $this->assertStringContainsString('Select a category to edit first.', $script);
    }

    public function test_categories_updated_toast_message_is_removed(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/content_page/faq_management.js'));

        $this->assertIsString($script);
        $this->assertStringNotContainsString('Categories updated', $script);
    }
}
