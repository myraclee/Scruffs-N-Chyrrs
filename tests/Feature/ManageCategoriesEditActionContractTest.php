<?php

namespace Tests\Feature;

use Tests\TestCase;

class ManageCategoriesEditActionContractTest extends TestCase
{
    public function test_action_buttons_in_categories_table_are_explicit_non_submit_buttons(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/content_page/manage_categories_modal.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString(
            '<button type="button" class="action_btn edit_btn"',
            $script,
            'Edit action button must be type="button" to avoid unintended form submit.',
        );
        $this->assertStringContainsString(
            '<button type="button" class="action_btn delete_btn"',
            $script,
            'Delete action button must be type="button" to avoid unintended form submit.',
        );
    }

    public function test_save_handler_ignores_non_save_submitters(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/content_page/manage_categories_modal.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('const submitter = e.submitter;', $script);
        $this->assertStringContainsString('submitter.classList.contains("submit_btn")', $script);
    }

    public function test_category_table_is_inside_manage_categories_form_context(): void
    {
        $blade = file_get_contents(base_path('resources/views/owner/pages/content_management.blade.php'));

        $this->assertIsString($blade);

        $formPos = strpos($blade, '<form id="categoryForm">');
        $tableBodyPos = strpos($blade, '<tbody id="categoryTableBody">');
        $formClosePos = strpos($blade, '</form>', $formPos !== false ? $formPos : 0);

        $this->assertNotFalse($formPos, 'Manage categories form was not found.');
        $this->assertNotFalse($tableBodyPos, 'Category table body was not found.');
        $this->assertNotFalse($formClosePos, 'Manage categories form closing tag was not found.');

        $this->assertTrue(
            $formPos < $tableBodyPos && $tableBodyPos < $formClosePos,
            'Category table should remain inside form context; action buttons must remain explicit non-submit controls.',
        );
    }
}
