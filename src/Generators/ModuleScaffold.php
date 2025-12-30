<?php

namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleSDK\Helpers\AcademicLevelManager;
use SchoolPalm\ModuleSDK\Helpers\Helper;
use SchoolPalm\ModuleSDK\Manifest\ManifestFactory;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

class ModuleScaffold
{
    protected const STRUCTURE_FILE = 'module_structure.json';

    public function make(string $manifestPath): array
    {
        $data = ManifestFactory::loadManifest($manifestPath);


        $vendor = Str::studly($data['vendor']);
        $module = Str::studly(Helper::moduleFolderName($data['module_key']));

        // Resolve academic levels strictly from manifest
        $levels = $data['level'] ?? [0];
        $level  = AcademicLevelManager::joinByCodes($levels);

        $base = config('schoolpalm.modules_path', base_path('modules'));

        // Expected root (MUST already exist)
        $root = "{$base}/{$vendor}/{$level}/{$module}";

        if (!File::exists($root)) {
            throw new \RuntimeException(
                "Module root folder not found.\nExpected: {$root}\nThis usually means the module level was changed manually in manifest.json.\nStructure generation has been aborted."
            );
        }

        // Generate sub-structure
        $paths = [
            'root'        => $root,
            'contracts'   => "{$root}/Contracts",
            'actions'     => "{$root}/Actions",
            'services'    => "{$root}/Services",
            'models'      => "{$root}/Models",
            'events'      => "{$root}/Events",
            'listeners'   => "{$root}/Listeners",
            'migrations'  => "{$root}/Migrations",
            'resources'   => "{$root}/Resources",
            'views'       => "{$root}/Resources/views",
            'lang'        => "{$root}/Resources/lang",
            'assets'      => "{$root}/Resources/assets",
            'js'          => "{$root}/Resources/js",
            'js_pages'    => "{$root}/Resources/js/Pages",
            'layouts'     => "{$root}/Resources/Layout",
            'components'  => "{$root}/Resources/js/Components",
            'composables' => "{$root}/Resources/js/composables",
            'providers'   => "{$root}/Providers",
            'tests'       => "{$root}/Tests",
        ];

        foreach ($paths as $key => $path) {
            if ($key === 'root') continue;
            File::ensureDirectoryExists($path);
        }

        $structure = [
            'module'    => $module,
            'singular'  => Str::singular($module),
            'namespace' => "{$vendor}\\{$level}\\{$module}",
            'paths'     => $paths
        ];

        // Save structure immediately
        self::saveStructure($root, $structure);

        return $structure;
    }

    /**
     * Save the module structure to a JSON file
     */
    public static function saveStructure(string $root, array $structure): void
    {
        $file = $root . '/' . self::STRUCTURE_FILE;
        File::put($file, json_encode($structure, JSON_PRETTY_PRINT));
    }

    /**
     * Load previously saved module structure
     */
    public static function loadStructure(string $root): ?array
    {
        $file = $root . '/' . self::STRUCTURE_FILE;
        if (!File::exists($file)) return null;

        $content = File::get($file);
        return json_decode($content, true);
    }


    /**
     * Remove stored module structure
     */
    public static function removeStructure(string $root): void
    {
        $file = $root . '/' . self::STRUCTURE_FILE;
        if (File::exists($file)) {
            File::delete($file);
        }
    }



public static function createStubFiles(): void
{
    $stubsDir = ModulePaths::stubsPath();
    $stubMapping = json_decode(File::get(ModulePaths::stubsMap()), true);


    // Build a lookup of allowed stub filenames
    $allowedFiles = array_values($stubMapping);

    // Delete existing .stub files NOT in mapping
    foreach (File::files($stubsDir) as $file) {
        if (
            $file->getExtension() === 'stub'
            && !in_array($file->getFilename(), $allowedFiles, true)
        ) {
            File::delete($file->getPathname());
        }
    }

    // Create missing stub files from mapping
    foreach ($stubMapping as $key => $fileName) {
        $filePath = rtrim($stubsDir, '/') . '/' . $fileName;

        if (!File::exists($filePath)) {
            File::put($filePath, "// Stub for {$key}");
        }
    }
}


}
