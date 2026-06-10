<?php

namespace SchoolPalm\ModuleSDK\Console;

use Illuminate\Support\Facades\File;
use SchoolPalm\ModuleBridge\Core\CreatedModuleRegistry;
use SchoolPalm\ModuleSDK\Core\ModuleRegistry;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

class RemoveModuleCommand extends ModuleCommandBase
{
    protected $signature = 'sp:rem-m';
    protected $description = 'Remove a module by deleting its folder and unregistering it from the registry';

    public function handle(): int
    {
        $module = $this->chooseModule();

        if (!$module) {
            $this->error('Module not found in registry.');
            return self::FAILURE;
        }

        $modulePath = $module['path'];

        if (!File::exists($modulePath)) {
            $this->warn("Module folder does not exist: {$modulePath}");
        } else {
            File::deleteDirectory($modulePath);
            $this->info(" Deleted module folder: {$modulePath}");
        }

        // Remove from registry
        (new CreatedModuleRegistry(ModulePaths::registryFile()))->remove($module['module_key']);
        
        $this->info(" Module [{$module['module_key']}] removed from registry.");

        return self::SUCCESS;
    }
}
