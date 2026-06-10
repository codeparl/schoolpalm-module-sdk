<?php

namespace SchoolPalm\ModuleSDK\Console;

use Illuminate\Support\Facades\File;
use SchoolPalm\ModuleSDK\Manifest\ManifestValidator;
use SchoolPalm\ModuleSDK\Manifest\ManifestFactory;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

class ValidateModuleCommand extends ModuleCommandBase
{
    protected $signature = 'sp:validate-m';
    protected $description = 'Validate the manifest.json of a registered SchoolPalm module';

    public function handle(): int
    {
        // Let user choose a module from registry

        $module = $this->chooseModule();
        if (!$module) {
            $this->error('Module not found in registry.');
            return self::FAILURE;
        }


        $manifestFile = $module['manifest'];

        if (!File::exists($manifestFile)) {
            $this->error("manifest.json not found at: {$manifestFile}");
            return self::FAILURE;
        }

        $manifest = ManifestFactory::loadManifest($manifestFile);

        

        if ($manifest === null) {
            $this->error("Failed to parse manifest.json. Invalid JSON.");
            return self::FAILURE;
        }

           $success = ManifestValidator::validate($manifest, $this);


        if ($success) {
            $this->info("✔ manifest.json is valid according to the schema.");
            return self::SUCCESS;
        }



        return self::FAILURE;
    }
}
