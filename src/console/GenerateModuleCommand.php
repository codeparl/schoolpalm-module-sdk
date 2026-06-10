<?php

namespace SchoolPalm\ModuleSDK\Console;

use Illuminate\Support\Facades\File;
use SchoolPalm\ModuleSDK\Generators\ModuleScaffold;

class GenerateModuleCommand extends ModuleCommandBase
{
    protected $signature = 'sp:gen-s';
    protected $description = 'Generate module folder structure from registered module manifest';

    public function handle(): int
    {

        /* -------------------------------------------------------------
         | Resolve module from registry
         |-------------------------------------------------------------*/
        $module = $this->chooseModule();
        if (!$module) {
            $this->error('Module not found in registry.');
            return self::FAILURE;
        }

        /* -------------------------------------------------------------
         | Resolve manifest path
         |-------------------------------------------------------------*/
        $manifestPath = rtrim($module['path'], DIRECTORY_SEPARATOR) . '/manifest.json';

        if (!File::exists($manifestPath)) {
            $this->error("manifest.json not found at: {$manifestPath}");
            return self::FAILURE;
        }

        /* -------------------------------------------------------------
         | Generate scaffold
         |-------------------------------------------------------------*/
        $this->info("⚙️ Generating structure:");
        $this->line("• Module: {$module['module_key']}");
        $this->line("• Namespace: {$module['namespace']}");
        $this->line("• Manifest: {$manifestPath}");

        $scaffold = app(ModuleScaffold::class);
        $ctx = $scaffold->make($manifestPath);

        /* -------------------------------------------------------------
         | Output
         |-------------------------------------------------------------*/
        $this->info('✅ Module structure generated successfully.');
       // $this->line("📁 Root path: {$ctx['paths']['root']}");

        return self::SUCCESS;
    }
}
