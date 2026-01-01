<?php

namespace SchoolPalm\ModuleSDK\Console;

use Illuminate\Support\Facades\File;
use SchoolPalm\ModuleSDK\Core\ModuleRegistry;
use SchoolPalm\ModuleSDK\Helpers\ModuleInstaller;

class installModuleCommand  extends ModuleCommandBase
{
    protected $signature = 'sp:install';
    protected $description = 'install  module';

    protected ModuleRegistry $registry;

    public function handle(): int
    {
        /* -------------------------------------------------------------
         | Choose module from registry
         |-------------------------------------------------------------*/
        //  $module = $this->chooseModule();
        // if (!$module) {
        //     $this->error('Module not found in registry.');
        //     return self::FAILURE;
        // }

$this->info("copying vendor routes to app routes...");
        ModuleInstaller::make()
        ->ensureInstallPaths()
        ->installRoutes();
    
        $this->info("Routes copied successfully");

       
        return self::SUCCESS;
    }
}
