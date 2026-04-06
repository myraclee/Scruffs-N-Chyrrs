<?php

namespace Tests\Feature;

use Tests\TestCase;

class OwnerContentManagementModalLayeringContractTest extends TestCase
{
    public function test_content_management_blade_contains_delete_confirm_overlay_ids(): void
    {
        $blade = file_get_contents(base_path('resources/views/owner/pages/content_management.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringContainsString('id="deleteConfirmModal"', $blade);
        $this->assertStringContainsString('id="products_delete_confirm_modal"', $blade);
        $this->assertStringContainsString('id="deleteTemplateModalOverlay"', $blade);
        $this->assertStringContainsString('id="rushDeleteConfirmOverlay"', $blade);
        $this->assertStringContainsString('id="deleteCategoryConfirmOverlay"', $blade);
        $this->assertStringContainsString('id="deleteFaqModalOverlay"', $blade);
    }

    public function test_delete_confirm_overlays_are_layered_above_parent_modals(): void
    {
        $homeCss = file_get_contents(base_path('resources/css/owner/pages/content_management/home_page_content.css'));
        $productsCss = file_get_contents(base_path('resources/css/owner/pages/content_management/products_page_content.css'));
        $orderTemplateCss = file_get_contents(base_path('resources/css/owner/pages/content_management/order_template.css'));
        $faqCss = file_get_contents(base_path('resources/css/owner/pages/content_management/faq_management.css'));

        $this->assertIsString($homeCss);
        $this->assertIsString($productsCss);
        $this->assertIsString($orderTemplateCss);
        $this->assertIsString($faqCss);

        $this->assertGreaterThan(
            $this->extractZIndex($homeCss, '#addSampleModal'),
            $this->extractZIndex($homeCss, '#deleteConfirmModal'),
            'Delete Sample confirm overlay must be above the Add/Edit Sample modal.',
        );

        $this->assertGreaterThan(
            $this->extractZIndex($productsCss, '#products_modal'),
            $this->extractZIndex($productsCss, '#products_delete_confirm_modal'),
            'Delete Product confirm overlay must be above the Product modal.',
        );

        $this->assertGreaterThan(
            $this->extractZIndex($orderTemplateCss, '#templateModalOverlay'),
            $this->extractZIndex($orderTemplateCss, '#deleteTemplateModalOverlay'),
            'Delete Template confirm overlay must be above the Order Template modal.',
        );

        $this->assertGreaterThan(
            $this->extractZIndex($orderTemplateCss, '#rushFeeModalOverlay'),
            $this->extractZIndex($orderTemplateCss, '#rushDeleteConfirmOverlay'),
            'Delete Rush Fee confirm overlay must be above the Rush Fee modal.',
        );

        $this->assertGreaterThan(
            $this->extractZIndex($faqCss, '.add_faq_modal'),
            $this->extractZIndex($faqCss, '#deleteFaqModalOverlay'),
            'Delete FAQ confirm overlay must be above the FAQ modal.',
        );

        $this->assertGreaterThan(
            $this->extractZIndex($faqCss, '#manageCategoriesOverlay'),
            $this->extractZIndex($faqCss, '#deleteCategoryConfirmOverlay'),
            'Delete Category confirm overlay must be above the Manage Categories modal overlay.',
        );
    }

    public function test_faq_styles_scope_overlay_rules_to_owned_ids(): void
    {
        $faqCss = file_get_contents(base_path('resources/css/owner/pages/content_management/faq_management.css'));

        $this->assertIsString($faqCss);
        $this->assertStringContainsString('#manageCategoriesOverlay', $faqCss);
        $this->assertStringContainsString('#deleteCategoryConfirmOverlay', $faqCss);
        $this->assertStringContainsString('#deleteFaqModalOverlay', $faqCss);
        $this->assertDoesNotMatchRegularExpression('/(?m)^\s*\.modal_overlay\s*\{/', $faqCss);
        $this->assertDoesNotMatchRegularExpression('/(?m)^\s*\.modal_overlay\.active\s*\{/', $faqCss);
    }

    private function extractZIndex(string $css, string $selector): int
    {
        $selectorPattern = preg_quote($selector, '/');
        $matched = preg_match_all('/' . $selectorPattern . '\\s*\\{([^}]*)\\}/s', $css, $blockMatches);

        $this->assertGreaterThan(0, $matched, sprintf('Expected a CSS block for selector %s.', $selector));

        for ($i = count($blockMatches[1]) - 1; $i >= 0; $i--) {
            $block = $blockMatches[1][$i] ?? '';
            $zMatch = preg_match('/z-index:\\s*([0-9]+)/', $block, $zIndexMatches);

            if ($zMatch === 1) {
                return (int) ($zIndexMatches[1] ?? 0);
            }
        }

        $this->fail(sprintf('Expected selector %s to define a numeric z-index.', $selector));

        return 0;
    }
}
