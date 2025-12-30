<?php

namespace SchoolPalm\ModuleSDK\Console;

use SchoolPalm\ModuleSDK\Core\ModuleRegistry;
use SchoolPalm\ModuleSDK\Generators\ModuleScaffold;

class GenerateStubMapCommand  extends ModuleCommandBase
{
    protected $signature = 'sp:stub-map';
    protected $description = 'Generate service pipeline (contract + service class) for a registered module';

    protected ModuleRegistry $registry;

    public function handle(): int
    {

    // Create all module stub files automatically
    ModuleScaffold::createStubFiles();
        return self::SUCCESS;
    }
}
