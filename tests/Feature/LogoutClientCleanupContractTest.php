<?php

namespace Tests\Feature;

use Tests\TestCase;

class LogoutClientCleanupContractTest extends TestCase
{
    public function test_customer_logout_script_clears_app_specific_storage_on_logout_submit(): void
    {
        $script = file_get_contents(base_path('resources/js/customeraccount_options.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('sessionStorage.removeItem("auth_toast_message");', $script);
        $this->assertStringContainsString('key.startsWith("form_state_")', $script);
        $this->assertStringContainsString('logoutForm.addEventListener("submit"', $script);
    }

    public function test_owner_logout_script_clears_app_specific_storage_on_logout_submit(): void
    {
        $script = file_get_contents(base_path('resources/js/owner/sidebar_account.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('sessionStorage.removeItem("auth_toast_message");', $script);
        $this->assertStringContainsString('key.startsWith("form_state_")', $script);
        $this->assertStringContainsString('logoutForm.addEventListener("submit"', $script);
    }
}
