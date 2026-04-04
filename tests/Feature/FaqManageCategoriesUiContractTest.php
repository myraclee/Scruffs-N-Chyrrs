<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqManageCategoriesUiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_content_page_contains_manage_categories_controls(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->get('/owner/pages/content_management');

        $response->assertOk();

        $html = $response->getContent();

        $manageBtnPos = strpos($html, 'id="addCategoryBtn"');
        $addFaqBtnPos = strpos($html, 'id="add_faq_btn"');
        $overlayPos = strpos($html, 'id="manageCategoriesOverlay"');
        $modalPos = strpos($html, 'id="manageCategoriesModal"');

        $this->assertNotFalse($manageBtnPos, 'Manage Categories button id was not found.');
        $this->assertNotFalse($addFaqBtnPos, 'Add FAQ button id was not found.');
        $this->assertNotFalse($overlayPos, 'Manage Categories overlay id was not found.');
        $this->assertNotFalse($modalPos, 'Manage Categories modal id was not found.');

        $this->assertTrue(
            $manageBtnPos < $addFaqBtnPos,
            'Expected Manage Categories button to appear before Add FAQ in action row markup.',
        );
    }

    public function test_content_management_blade_uses_faq_styles_and_not_legacy_manage_categories_stylesheet(): void
    {
        $blade = file_get_contents(base_path('resources/views/owner/pages/content_management.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringContainsString(
            "resources/css/owner/pages/content_management/faq_management.css",
            $blade,
        );
        $this->assertStringNotContainsString(
            "resources/css/owner/pages/content_management/manage_categories.css",
            $blade,
        );
    }

    public function test_manage_categories_button_has_stable_non_transparent_base_style(): void
    {
        $css = file_get_contents(base_path('resources/css/owner/pages/content_management/faq_management.css'));

        $this->assertIsString($css);

        preg_match('/\\.manage_categories_button\\s*\\{([^}]*)\\}/s', $css, $matches);
        $buttonBlock = $matches[1] ?? '';

        $this->assertNotSame('', $buttonBlock, 'Expected .manage_categories_button CSS block to exist.');
        $this->assertStringContainsString('display: inline-flex;', $buttonBlock);
        $this->assertStringContainsString('align-items: center;', $buttonBlock);
        $this->assertStringContainsString('justify-content: center;', $buttonBlock);
        $this->assertStringContainsString('opacity: 1;', $buttonBlock);
        $this->assertStringContainsString('transform: none;', $buttonBlock);
    }
}
