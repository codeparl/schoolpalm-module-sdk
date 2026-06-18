<?php

namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleSDK\Helpers\Helper;
use SchoolPalm\ModuleSDK\Manifest\ManifestFactory;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

class ModuleScaffold
{
    protected const STRUCTURE_FILE = 'module_structure.json';

    public function make(string|array $manifestPath): array
    {
        if (is_string($manifestPath))
            $data = ManifestFactory::loadManifest($manifestPath);
        else  $data  =  $manifestPath;


        $module = Str::studly(Helper::moduleFolderName($data['module_key']));
        $base = config('schoolpalm.modules_path', base_path('modules'));

        // Expected root (MUST already exist)
        //first remove Backend  part
        $root =  $base . '/' .  $data['root'];

        if (!File::exists($root)) {
            throw new \RuntimeException(
                "Module root folder not found.\nExpected: {$root}\nThis usually means the module level was changed manually in manifest.json.\nStructure generation has been aborted."
            );
        }

        $frontend_paths =  $this->frontend($root);
        $backend_paths =  $this->backend($root);


        $structure = [
            'module'    => $module,
            'root' => $data['root'],
            'singular'  => Str::singular($module),
            'namespace' => $data['namespace'],
            'paths'     => [
                'frontend' => $frontend_paths,
                'backend' => $backend_paths,

            ]
        ];

        // Save structure immediately
        self::saveStructure($root, $structure);

        return $structure;
    }


    private function ensureDirectory(array $paths)
    {
        foreach ($paths as $key => $path) {
            if ($key === 'root') continue;
            File::ensureDirectoryExists($path);
        }
    }

    /**
     * create backend files and folders
     */
    public function backend(string $root): array
    {
        $_root = $root;
        $root .= '/Backend';
        $paths = [
            'contracts'   => "{$root}/Contracts",
            'contracts-core'   => "{$root}/Contracts/Core",
            'facades'   => "{$root}/Facades",
            'actions'     => "{$root}/Actions",
            'services'    => "{$root}/Services",
            'services-core'    => "{$root}/Services/core",
            'models'      => "{$root}/Models",
            'dtos'      => "{$root}/DTOs",
            'traits'   => "{$root}/Traits",
            'events'      => "{$root}/Events",
            'listeners'   => "{$root}/Listeners",
            'database'  => "{$root}/Database",
            'migrations'  => "{$root}/Database/migrations",
            'seeder'  => "{$root}/Database/Seeders",
            'providers'   => "{$root}/Providers",
            'relations'   => "{$root}/Relations",
            'tests'       => "{$root}/Tests"

        ];

        $this->ensureDirectory($paths);

        return $paths;
    }

    /**
     * create frontend files and folders
     */
    public function frontend(string $root): array
    {

        $root   .= '/Frontend';

        $paths = [
            'src'   => "{$root}/src",
            'assets'   => "{$root}/src/assets",
            'pages'   => "{$root}/src/Pages",
            'router'   => "{$root}/src/router",
            'stores'   => "{$root}/src/stores",
        ];

        $this->ensureDirectory($paths);
        return $paths;
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
