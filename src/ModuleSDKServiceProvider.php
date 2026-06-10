<?php

namespace SchoolPalm\ModuleSDK;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use SchoolPalm\ModuleSDK\Console\GenerateModuleCommand;
use SchoolPalm\ModuleSDK\Console\GenerateStubMapCommand;
use SchoolPalm\ModuleSDK\Console\GenerateStubsCommand;
use SchoolPalm\ModuleSDK\Console\MakeModuleCommand;
use SchoolPalm\ModuleSDK\Console\RemoveModuleCommand;
use SchoolPalm\ModuleSDK\Console\ValidateModuleCommand;
use SchoolPalm\ModuleSDK\Console\GenerateDtoFromSchemaCommand;
use SchoolPalm\ModuleBridge\Support\EncryptedConfig;
use SchoolPalm\ModuleBridge\Support\Helper as BridgeHelper;
use SchoolPalm\ModuleSDK\Console\installModuleCommand;
use SchoolPalm\ModuleSDK\Console\RemoveAllModuleCommand;
use SchoolPalm\ModuleSDK\Console\ShouldModuleRunCommand;
use SchoolPalm\ModuleSDK\Core\MenuManager;
use SchoolPalm\ModuleSDK\Support\ModulePaths;
use SchoolPalm\ModuleBridge\Providers\ModuleRegistrar;
use SchoolPalm\ModuleSDK\Console\FetchConfigCommand;

class ModuleSDKServiceProvider extends ServiceProvider
{
    public static array $academicLevels = [];
    public function register()
    {

        $this->app->singleton('menu.manager', function ($app) {
            return new MenuManager();
        });

        // Merge package config into Laravel app config
        $this->mergeConfigFrom(
            ModulePaths::configPath(),
            'sdk'
        );

        $this->mergeConfigFrom(
            BridgeHelper::configPath(),
            'sdk'
        );
        ModuleRegistrar::registerSnapshots(config('sdk.snapshot.registry_path'));
        ModuleRegistrar::registerModules(ModulePaths::registryFile(),'sdk');
        ModuleRegistrar::bootRelations(ModulePaths::registryFile(),null,'sdk');

        //dd(ModuleRegistrar::getRelationRegistry());

        //ModuleRegistrar::debugRegistration(ModulePaths::registryFile(),'sdk');
        // Bind the SDK BaseModule to the Bridge
        // Bridge::bind(BaseModule::class);

        // Load and cache academic levels
        EncryptedConfig::init();
        self::$academicLevels =  BridgeHelper::getAcademicLevels();
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/sdk.php' => config_path('sdk.php'),
        ], 'schoolpalm-config');

        Config::set('sdk.registry_path', ModulePaths::registryFile());
        if ($this->app->runningInConsole()) {


            $this->commands([
                MakeModuleCommand::class,
                GenerateModuleCommand::class,
                ValidateModuleCommand::class,
                RemoveModuleCommand::class,
                GenerateStubsCommand::class,
                GenerateStubMapCommand::class,
                installModuleCommand::class,
                RemoveAllModuleCommand::class,
                ShouldModuleRunCommand::class,
                FetchConfigCommand::class,
                GenerateDtoFromSchemaCommand::class
            ]);
        }
    }
}
