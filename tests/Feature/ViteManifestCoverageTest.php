<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Tests\TestCase;

class ViteManifestCoverageTest extends TestCase
{
    /**
     * @return array<int, string>
     */
    private function getBladeViteAssets(): array
    {
        $bladeDir = base_path('resources/views');
        $assets = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($bladeDir)
        );

        $bladeFiles = new RegexIterator($iterator, '/^.+\.blade\.php$/i');

        foreach ($bladeFiles as $fileInfo) {
            $content = file_get_contents($fileInfo->getPathname());

            if ($content === false || strpos($content, '@vite(') === false) {
                continue;
            }

            preg_match_all('/@vite\((.*?)\)/s', $content, $viteCalls);

            foreach ($viteCalls[1] as $viteCall) {
                preg_match_all('/[\"\'](resources\/[^\"\']+\.(?:css|js))[\"\']/', $viteCall, $matches);

                foreach ($matches[1] as $assetPath) {
                    $assets[] = $assetPath;
                }
            }
        }

        $assets = array_values(array_unique($assets));
        sort($assets);

        return $assets;
    }

    public function test_all_blade_vite_assets_exist_on_disk(): void
    {
        $missingAssets = [];

        foreach ($this->getBladeViteAssets() as $assetPath) {
            if (!file_exists(base_path($assetPath))) {
                $missingAssets[] = $assetPath;
            }
        }

        $this->assertSame(
            [],
            $missingAssets,
            'These @vite assets are referenced by Blade but missing on disk: ' . implode(', ', $missingAssets)
        );
    }

    public function test_all_blade_vite_assets_exist_in_manifest(): void
    {
        $manifestPath = public_path('build/manifest.json');

        $this->assertFileExists(
            $manifestPath,
            'Vite manifest not found at public/build/manifest.json. Run "npm run build" first.'
        );

        $manifestJson = file_get_contents($manifestPath);
        $this->assertNotFalse($manifestJson, 'Unable to read Vite manifest file.');

        $manifest = json_decode($manifestJson, true);
        $this->assertIsArray($manifest, 'Vite manifest contains invalid JSON.');

        $missingManifestEntries = [];

        foreach ($this->getBladeViteAssets() as $assetPath) {
            if (!array_key_exists($assetPath, $manifest)) {
                $missingManifestEntries[] = $assetPath;
            }
        }

        $this->assertSame(
            [],
            $missingManifestEntries,
            'These @vite assets are referenced by Blade but missing from manifest: ' . implode(', ', $missingManifestEntries)
        );
    }
}
