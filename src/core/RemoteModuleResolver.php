<?php

namespace SchoolPalm\ModuleSDK\core;

use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Platform\ModuleStoreClient;
use SchoolPalm\ModuleBridge\Support\Helper;
use SchoolPalm\ModuleSDK\Generators\SchemaMockDataGenerator;

class RemoteModuleResolver
{
    /**
     * Resolve all required module snapshots from a manifest
     */
    public static function resolveRequiredModules(array $manifest): void
    {
        $downloadPath = config('sdk.snapshot.path');
        $executionPath = config('sdk.snapshots_path');

        $requires = $manifest['requires'] ?? [];
        $requiredModules = $requires['modules'] ?? [];

        if (empty($requiredModules) || !is_array($requiredModules)) {
            return;
        }

        $client = new ModuleStoreClient(
            downloadPath: $downloadPath,
            executionPath: $executionPath
        );

        foreach ($requiredModules as $moduleKey => $version) {

            if (!is_string($moduleKey) || !is_string($version)) {
                continue;
            }

            /**
             * 1. Resolve snapshot
             */
          $executionPath =    $client->resolve($moduleKey, $version);

            /**
             * 2. Generate mock data AFTER resolution
             */
            self::generateMockDataForModule($executionPath,$moduleKey);
        }
    }

    /**
     * Generate Faker-based mock data for resolved module
     */
    protected static function generateMockDataForModule(string $snapshotPath, string $moduleKey): void
    {
        $path = Helper::moduleKeyToNamespace($moduleKey);
        $path =  Helper::namespaceToPath($path);
        $schemasPath = $snapshotPath . $path.'/Database/schemas';
        $outputPath  = $snapshotPath . $path.'/data';

        if (!is_dir($schemasPath)) {
            return;
        }

        // Load manifest to get contracts
        $manifestPath = $snapshotPath.Str::before($path ,'Backend'). 'snapshot.manifest.json';
        if (!is_file($manifestPath)) {
            return;
        }

        $manifest = Helper::loadJson($manifestPath);


        $contracts = $manifest['provides'] ?? [];

        if (empty($contracts)) {
            return;
        }

        $fakerGenerator = new SchemaMockDataGenerator();

        $fakerGenerator->generate(
            providedContracts: $contracts,
            schemasPath: $schemasPath,
            outputPath: $outputPath,
            rows: 15
        );
    }
}
