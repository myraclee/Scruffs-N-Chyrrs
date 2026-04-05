<?php

namespace Tests\Unit;

use Tests\TestCase;

class SignupBladeLintGuardTest extends TestCase
{
    public function test_signup_blade_does_not_embed_blade_directives_inside_inline_style_attributes(): void
    {
        $bladeFile = base_path('resources/views/customer/signup.blade.php');
        $contents = file_get_contents($bladeFile);

        $this->assertNotFalse($contents, 'Failed to read signup Blade file for lint guard check.');

        $matches = preg_match_all('/style\s*=\s*"[^"]*@(error|enderror)\b[^"]*"/i', (string) $contents);

        $this->assertSame(
            0,
            $matches,
            'Blade directives were found inside an inline style attribute. Move conditional styling to classes instead.'
        );
    }
}
