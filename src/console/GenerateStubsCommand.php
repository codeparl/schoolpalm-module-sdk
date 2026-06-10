<?php

namespace SchoolPalm\ModuleSDK\Console;

use SchoolPalm\ModuleSDK\Generators\StubGenerator;

class GenerateStubsCommand extends ModuleCommandBase
{
    protected $signature = 'sp:stub';
    protected $description = 'Generate service pipeline (contract + service class) for a registered module';

    public function handle(): int
    {
        /* -------------------------------------------------------------
         | Choose module from registry those that are not yet to run.
         | if we re-generate stub for running modules may replace file content
         |-------------------------------------------------------------*/
        $module = $this->chooseModule('not_run');
        if (!$module) {
            $this->error('Module not found in registry.');
            return self::FAILURE;
        }

        StubGenerator::frontend($module['module_key'], $this)
        ->devPage()
        ->entry()
        ->package()
         ->vite_config()
        ->vite_env()
        ->postcss()
        ->vue_shim()
        ->tsconfig()
        ->tailwind()
        ->css()
        ->appRuntime()
        ->vueApp()
        ->dashboard()
        ->routes()
        ->bootstrap()
        ->helper()
        ->quasarSetup()
        ->scripts();
        return self::SUCCESS;
    }

    
}
