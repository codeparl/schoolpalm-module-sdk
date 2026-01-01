<?php

namespace SchoolPalm\ModuleSDK;

use Illuminate\Support\ServiceProvider;
use SchoolPalm\ModuleBridge\Support\Bridge;
use SchoolPalm\ModuleSDK\Core\BaseModule;
use SchoolPalm\ModuleSDK\Console\GenerateModuleCommand;
use SchoolPalm\ModuleSDK\Console\GenerateStubMapCommand;
use SchoolPalm\ModuleSDK\Console\GenerateStubsCommand;
use SchoolPalm\ModuleSDK\Console\MakeModuleCommand;
use SchoolPalm\ModuleSDK\Console\RemoveModuleCommand;
use SchoolPalm\ModuleSDK\Console\ValidateModuleCommand;
use SchoolPalm\ModuleBridge\Support\EncryptedConfig;
use SchoolPalm\ModuleBridge\Support\Helper as BridgeHelper;
use SchoolPalm\ModuleSDK\Console\installModuleCommand;
use SchoolPalm\ModuleSDK\Console\RemoveAllModuleCommand;

class ModuleSDKServiceProvider extends ServiceProvider
{
    public static array $academicLevels = [];
    public function register()
    {
        // Merge package config into Laravel app config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/schoolpalm.php', 
            'schoolpalm' 
        );

        // Bind the SDK BaseModule to the Bridge
        Bridge::bind(BaseModule::class);
          
        // Load and cache academic levels 
        EncryptedConfig::init();
        self::$academicLevels =  BridgeHelper::getAcademicLevels();
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/schoolpalm.php' => config_path('schoolpalm.php'),
        ], 'schoolpalm-config');

        if ($this->app->runningInConsole()) {


            $this->commands([
                MakeModuleCommand::class,
                GenerateModuleCommand::class,
                ValidateModuleCommand::class,
                RemoveModuleCommand::class,
                GenerateStubsCommand::class,
                GenerateStubMapCommand::class,
                installModuleCommand::class,
                RemoveAllModuleCommand::class
            ]);
        }
    }
}
