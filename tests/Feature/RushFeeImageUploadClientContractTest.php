<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RushFeeImageUploadClientContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_content_management_page_contains_rush_fee_image_upload_controls(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this
            ->actingAs($owner)
            ->get('/owner/pages/content_management');

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('class="image_slot plus rush_image_upload_slot"', $html);
        $this->assertStringContainsString('id="rushImageUploadSlot"', $html);
        $this->assertStringContainsString('id="rushFeeImageInput"', $html);
        $this->assertStringContainsString('id="rushImageUploadStatus"', $html);
        $this->assertStringContainsString('id="rushImagePreviewWrap"', $html);
        $this->assertStringContainsString('id="rushImagePreviewStage"', $html);
        $this->assertStringContainsString('id="rushImagePreview"', $html);
        $this->assertStringContainsString('id="rushImageFullscreenBtn"', $html);
        $this->assertStringContainsString('id="rushImageRemoveBtn"', $html);
    }

    public function test_rush_fee_api_client_has_upload_image_method_bound_to_upload_endpoint(): void
    {
        $script = file_get_contents(base_path('resources/js/api/rushFeeApi.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('async uploadRushFeeImage(imageFile)', $script);
        $this->assertStringContainsString('formData.append("image", imageFile);', $script);
        $this->assertStringContainsString('`${this.baseUrl}/upload-image`', $script);
    }

    public function test_rush_fee_modal_script_uploads_image_and_attaches_image_url_to_save_payload(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/content_page/rush_fees.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('const rushImagePreviewStage = document.getElementById("rushImagePreviewStage");', $script);
        $this->assertStringContainsString('rushImageUploadSlot?.addEventListener("click", () => {', $script);
        $this->assertStringContainsString('rushImageInput?.click();', $script);
        $this->assertStringContainsString('rushImageInput?.addEventListener("change", handleRushImageSelected);', $script);
        $this->assertStringContainsString('rushImageFullscreenBtn?.addEventListener("click", toggleRushImageFullscreen);', $script);
        $this->assertStringContainsString('requestElementFullscreen(rushImagePreviewStage || rushImagePreview);', $script);
        $this->assertStringContainsString('rushImageUploadSlot?.classList.remove("hidden");', $script);
        $this->assertStringContainsString('rushImageUploadSlot?.classList.add("hidden");', $script);
        $this->assertStringContainsString('await rushApi.uploadRushFeeImage(file);', $script);
        $this->assertStringContainsString('draftFee.imageUrl = uploadedUrl;', $script);
        $this->assertStringContainsString('image_url: draftFee.imageUrl || "",', $script);
    }

    public function test_uploaded_image_renders_only_on_owner_rush_fee_cards(): void
    {
        $ownerScript = file_get_contents(base_path('resources/js/owner/content_page/rush_fees.js'));
        $customerScript = file_get_contents(base_path('resources/js/customer/pages/rush_fees_display.js'));

        $this->assertIsString($ownerScript);
        $this->assertIsString($customerScript);

        $this->assertStringContainsString('imageWrap.className = "rush_card_image_wrap";', $ownerScript);
        $this->assertStringContainsString('cardImage.className = "rush_card_image";', $ownerScript);
        $this->assertStringContainsString('const normalizedImageUrl = normalizeStorageImageUrl(', $ownerScript);

        $this->assertStringNotContainsString('rush_card_image_wrap', $customerScript);
        $this->assertStringNotContainsString('rush_card_image', $customerScript);
        $this->assertStringNotContainsString('image_url', $customerScript);
    }
}
