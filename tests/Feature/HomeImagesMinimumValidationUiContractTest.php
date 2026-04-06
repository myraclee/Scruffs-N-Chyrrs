<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeImagesMinimumValidationUiContractTest extends TestCase
{
    public function test_home_images_modal_blocks_save_when_no_images_selected(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/content_page/edit_home_images_modal.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('if (tempImages.length < 1)', $script);
        $this->assertStringContainsString('Toast.error("At least one home page image is required.");', $script);
        $this->assertStringContainsString('return;', $script);
    }

    public function test_home_images_api_client_exposes_server_validation_message(): void
    {
        $script = file_get_contents(base_path('resources/js/api/homeImageApi.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('firstValidationMessage', $script);
        $this->assertStringContainsString('Object.values(result.errors)[0]?.[0]', $script);
        $this->assertStringContainsString('throw new Error(errorMsg);', $script);
    }
}
