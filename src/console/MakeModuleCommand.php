<?php

namespace SchoolPalm\ModuleSDK\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Core\CreatedModuleRegistry;
use SchoolPalm\ModuleBridge\Support\Helper;
use SchoolPalm\ModuleSDK\Manifest\ManifestFactory;
use SchoolPalm\ModuleSDK\Support\ModulePaths;
use SchoolPalm\ModuleSDK\Manifest\ManifestValidator;
class MakeModuleCommand extends ModuleCommandBase
{
    protected $signature = 'sp:make-m {name?}';
    protected $description = 'Create a new SchoolPalm module (vendor inferred from config)';

    public function handle(): int
    {
        $this->info("🚀 Creating a new SchoolPalm module\n");

        $config = config('schoolpalm');

        /* -------------------------------------------------------------
         | Required input: Module name
         |-------------------------------------------------------------*/
        $name = $this->argument('name')
            ?? $this->ask('Module Name (human readable)');

        if (!$name) {
            $this->error('Module name is required.');
            return self::FAILURE;
        }

        /* -------------------------------------------------------------
         | Optional choices
         |-------------------------------------------------------------*/
        $role = $this->choice(
            'Minimum role',
            ['admin', 'teacher', 'student'],
            $config['defaults']['role'] === 'teacher' ? 1 : 0
        );

        /* -------------------------------------------------------------
         | Derived values (from config + name)
         |-------------------------------------------------------------*/
        $vendor     = $config['vendor'];
        $normalized = Str::slug($name, '_');
        $moduleKey  = $vendor . '.' . $normalized;
        $levels     = $config['defaults']['levels'];
        $is_common     = $config['defaults']['is_common'];

        /* -------------------------------------------------------------
         | Build manifest input
         |-------------------------------------------------------------*/
        $data = [
            'name'        => $name,
            'module_key'  => $moduleKey,
            'description' => '',
            'role'        => $role,
            'level'=>$levels,
            'is_common'=>$is_common

        ];

        /* -------------------------------------------------------------
         | Create manifest
         |-------------------------------------------------------------*/
        $manifest = ManifestFactory::make($data);


        /* -------------------------------------------------------------
         | Validate manifest
         |-------------------------------------------------------------*/
        $success =  ManifestValidator::validate($manifest, $this);

        if (!$success) {
            return self::FAILURE;
        }

        /* -------------------------------------------------------------
         | Create module directory
         |-------------------------------------------------------------*/
        $modulePath = ModulePaths::modulePath($manifest['module_key'],$levels);

        if (File::exists($modulePath)) {
            $this->error("❌ Module already exists: {$modulePath}");
            return self::FAILURE;
        }

        File::makeDirectory($modulePath, 0755, true);

        /* -------------------------------------------------------------
         | Write manifest.json
         |-------------------------------------------------------------*/
        Helper::storeJson('manifest',$manifest,$modulePath);

              /* -------------------------------------------------------------
         | Register module in package registry
         |-------------------------------------------------------------*/
        $register  = new CreatedModuleRegistry(ModulePaths::registryFile());
         $data = [
            'module_key' => $manifest['module_key'],
            'vendor'     => $manifest['vendor'],
            'levels'     => $manifest['level'] ?: [],
            'is_common'  => $manifest['is_common'],
            'role'     => $manifest['role'],
            'namespace'  => ModulePaths::moduleNamespace($manifest['module_key'], $manifest['level']),
            'path'       => $modulePath,
            'manifest'   => $modulePath . '/manifest.json'];

            $data['folder']= Helper::levelFolder($data['namespace']);
        
        $register ->register($data );
        $register->clearCache();

        $this->info("✅ Module [{$manifest['module_key']}] created successfully");
        $this->line("📁 Location: {$modulePath}");

        return self::SUCCESS;
    }


}
