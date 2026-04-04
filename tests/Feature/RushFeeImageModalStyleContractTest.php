<?php

namespace Tests\Feature;

use Tests\TestCase;

class RushFeeImageModalStyleContractTest extends TestCase
{
    public function test_rush_image_remove_button_uses_red_remove_style_contract(): void
    {
        $css = file_get_contents(base_path('resources/css/owner/pages/content_management/order_template.css'));

        $this->assertIsString($css);

        preg_match('/\\.rush_image_remove_btn\\s*\\{([^}]*)\\}/s', $css, $removeMatches);
        $removeBlock = $removeMatches[1] ?? '';

        $this->assertNotSame('', $removeBlock, 'Expected .rush_image_remove_btn CSS block to exist.');
        $this->assertStringContainsString('background-color: #c83333;', $removeBlock);
        $this->assertStringContainsString('width: 60px;', $removeBlock);
        $this->assertStringContainsString('height: 25px;', $removeBlock);

        preg_match('/\\.rush_image_remove_btn:hover\\s*\\{([^}]*)\\}/s', $css, $hoverMatches);
        $hoverBlock = $hoverMatches[1] ?? '';

        $this->assertNotSame('', $hoverBlock, 'Expected .rush_image_remove_btn:hover CSS block to exist.');
        $this->assertStringContainsString('background-color: #8d0e0e;', $hoverBlock);
    }

    public function test_rush_image_preview_fullscreen_style_contract_exists(): void
    {
        $css = file_get_contents(base_path('resources/css/owner/pages/content_management/order_template.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.rush_image_upload_slot {', $css);
        $this->assertStringContainsString('.rush_image_upload_slot.hidden {', $css);
        $this->assertStringContainsString('.rush_image_preview_stage:fullscreen,', $css);
        $this->assertStringContainsString('.rush_image_preview_stage:-webkit-full-screen,', $css);
        $this->assertStringContainsString('.rush_image_preview_stage:-moz-full-screen,', $css);
        $this->assertStringContainsString('.rush_image_preview_stage:-ms-fullscreen {', $css);
        $this->assertStringContainsString('align-items: center;', $css);
        $this->assertStringContainsString('justify-content: center;', $css);

        preg_match('/\\.rush_image_preview_stage:fullscreen \\.rush_image_preview,\\s*\\.rush_image_preview_stage:-webkit-full-screen \\.rush_image_preview,\\s*\\.rush_image_preview_stage:-moz-full-screen \\.rush_image_preview,\\s*\\.rush_image_preview_stage:-ms-fullscreen \\.rush_image_preview\\s*\\{([^}]*)\\}/s', $css, $fullscreenImageMatches);
        $fullscreenImageBlock = $fullscreenImageMatches[1] ?? '';

        $this->assertNotSame('', $fullscreenImageBlock, 'Expected rush fullscreen image sizing block to exist.');
        $this->assertStringContainsString('width: auto;', $fullscreenImageBlock);
        $this->assertStringContainsString('height: auto;', $fullscreenImageBlock);
        $this->assertStringContainsString('max-width: 100vw;', $fullscreenImageBlock);
        $this->assertStringContainsString('max-height: 100vh;', $fullscreenImageBlock);
    }
}
