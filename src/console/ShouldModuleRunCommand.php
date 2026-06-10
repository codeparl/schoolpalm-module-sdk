<?php

namespace SchoolPalm\ModuleSDK\Console;

use SchoolPalm\ModuleBridge\Facades\CreatedRegistry;
use SchoolPalm\ModuleSDK\Core\ModuleManager;

class ShouldModuleRunCommand extends ModuleCommandBase
{
    protected $signature = 'sp:set-run';
    protected $description = 'Generate service pipeline (contract + service class) for a registered module';

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

       $manager  =  new ModuleManager($module['module_key']);
      

        $this->newLine();
        $answer = $this->ask('Enable module to run in dev-server. (yes/no)','no');
       
        if(strtolower($answer) == 'yes')
        $manager->shouldRun(true);
        else $manager->shouldRun(false);
         
        $this->newLine();
        $this->info("Module {$module['module_key']} updated. ");

        return self::SUCCESS;
    }

    
}
