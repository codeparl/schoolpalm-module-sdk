<?php

namespace SchoolPalm\ModuleSDK\Console;

use Illuminate\Support\Facades\File;
use SchoolPalm\ModuleSDK\Core\ModuleRegistry;
use SchoolPalm\ModuleSDK\Helpers\Helper;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

class GenerateStubsCommand extends ModuleCommandBase
{
    protected $signature = 'sp:stub';
    protected $description = 'Generate service pipeline (contract + service class) for a registered module';

    protected ModuleRegistry $registry;

    public function handle(): int
    {
        /* -------------------------------------------------------------
         | Choose module from registry
         |-------------------------------------------------------------*/
        $module = $this->chooseModule();
        if (!$module) {
            $this->error('Module not found in registry.');
            return self::FAILURE;
        }

        // Load stub mapping and module structure
        $stubFiles = Helper::loadJson(ModulePaths::stubsMap());
        $moduleStructure = Helper::loadJson($module['path'] . '/module_structure.json');

        if (!$moduleStructure) {
            $this->error('Module structure not found. Make sure the module is scaffolded.');
            return self::FAILURE;
        }

        // Loop over module paths and generate files from stubs
        foreach ($moduleStructure['paths'] as $key => $path) {

            if (!isset($stubFiles[$key])) {
                $this->warn("No stub defined for key: {$key}, skipping...");
                continue;
            }

            $stubFile = ModulePaths::stubsPath().'/'. $stubFiles[$key];

            if (!File::exists($stubFile)) {
                $this->warn("Stub file not found: {$stubFile}, skipping...");
                continue;
            }

            $fileName = basename(str_replace('.stub', '', $stubFiles[$key]));
           $fileName =  ($key == 'root' ? '': $moduleStructure['singular']).$fileName;
            $targetPath = rtrim($path, '/') . '/' . $fileName;

            // Create the file with dynamic placeholders
            $this->publishStub($stubFile, $targetPath, [
                'module' => $moduleStructure['module'],
                'singular' => $moduleStructure['singular'],
                'namespace'  => $moduleStructure['namespace'],
                'action'  => $moduleStructure['module'],
                'className'  => ucfirst($key),
            ]);

            $this->info("Created stub for {$key}: {$targetPath}");
        }

        return self::SUCCESS;
    }

    /**
     * Publish stub file by replacing placeholders
     */
    protected function publishStub(string $stubPath, string $targetPath, array $placeholders): void
    {
        $content = File::get($stubPath);

        foreach ($placeholders as $key => $value) {
            $content = str_replace("{{ {$key} }}", $value, $content);
        }

        $dir = dirname($targetPath);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($targetPath, $content);
    }
}
