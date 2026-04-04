<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductSampleModalStyleContractTest extends TestCase
{
    public function test_sample_modal_script_uses_isolated_add_slot_selector(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/content_page/product_sample_modal.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString("querySelector('.sample_add_slot')", $script);
        $this->assertStringContainsString("addBox.className = 'image_slot plus sample_add_slot';", $script);
        $this->assertStringNotContainsString("querySelector('.sample_add_box')", $script);
        $this->assertStringNotContainsString("querySelector('.products_add_box')", $script);
    }

    public function test_remove_button_css_uses_non_overlapping_flow_layout(): void
    {
        $css = file_get_contents(base_path('resources/css/owner/pages/content_management/home_page_content.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.sample_add_slot {', $css);
        $this->assertStringNotContainsString('.sample_add_box {', $css);

        preg_match('/\\.sample_image_wrapper\\s*\\{([^}]*)\\}/s', $css, $wrapperMatches);
        $wrapperBlock = $wrapperMatches[1] ?? '';

        $this->assertNotSame('', $wrapperBlock, 'Expected .sample_image_wrapper CSS block to exist.');
        $this->assertStringContainsString('display: flex;', $wrapperBlock);
        $this->assertStringContainsString('flex-direction: column;', $wrapperBlock);

        preg_match('/\\.remove_sample_image\\s*\\{([^}]*)\\}/s', $css, $removeMatches);
        $removeBlock = $removeMatches[1] ?? '';

        $this->assertNotSame('', $removeBlock, 'Expected .remove_sample_image CSS block to exist.');
        $this->assertStringNotContainsString('position: absolute', $removeBlock);
        $this->assertStringNotContainsString('bottom: -', $removeBlock);
    }
}
