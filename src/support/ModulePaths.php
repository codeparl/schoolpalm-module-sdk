<?php

namespace SchoolPalm\ModuleSDK\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Support\LevelManager;
use SchoolPalm\ModuleBridge\Support\Helper as bridgeHelper;

class ModulePaths
{
    /**
     * Base path for all modules (on filesystem)
     */
    public static function modulesBasePath(): string
    {
        return config('schoolpalm.modules_path', base_path('modules'));
    }

    /**
     * Path to stubs
     */
    public static function stubsPath(): string
    {
        return __DIR__ . '/../../stubs';
    }

    public static function basePath(string $path = ''): string
    {
        return realpath(__DIR__ . '/../') . '/' . $path;
    }

    public static function configPath(): string
    {
        return realpath(__DIR__ . '/../../config/sdk.php');
    }

    public static function stubsMap(): string
    {
        return __DIR__ . '/../../stubs/stubs.json';
    }
    /**
     * Path to manifest schema
     */
    public static function schemaPath(): string
    {
        return __DIR__ . '/../module-manifest.schema.json';
    }

    public static function moduleRegisterPath(): string
    {
        return realpath(__DIR__ . '/../../');
    }

    public static function registryFile(): string
    {
        return self::moduleRegisterPath() . '/modules.json';
    }

    /**
     * Resolve levels for folder/namespace based on rules:
     * 1. Provided levels → use them
     * 2. No levels + is_common=false → [0] → folder = Common
     * 3. No levels + is_common=true → folder = Common
     */
    protected static function resolveLevels(?array $levels, bool $isCommon): array
    {
        if (!empty($levels)) {
            return $levels;
        }

        return $isCommon ? [0] : [0];
    }

    /**
     * Module folder path: vendor/level/module
     */
    public static function modulePath(string $root): string
    {
        return self::modulesBasePath()
            . '/' . $root;
    }

    /**
     * Module PHP namespace: vendor\level\module
     * NOTE: Excludes the base folder like 'Modules'
     */
    public static function moduleNamespace(array $module, string $levelFolder): string
    {
        return bridgeHelper::moduleNamespace($module, $levelFolder);
    }

    /**
     * Namespace for module models
     */
    public static function moduleModelsNamespace(array $module, string $levelFolder): string
    {
        return self::moduleNamespace($module, $levelFolder) . '\\Models';
    }

    /**
     * Vue/frontend path: vendor/level/role/module
     */
    public static function vuePath(
        string $vendor,
        string $module,
        ?array $levels = null,
        bool $isCommon = true,
        string $role = 'Admin'
    ): string {
        $resolvedLevels = self::resolveLevels($levels, $isCommon);

        $levelFolder = LevelManager::joinByCodes($resolvedLevels);

        return resource_path(
            "{$vendor}/{$levelFolder}/{$role}/{$module}"
        );
    }

    /**
     * Check if a module exists on the filesystem
     *
     * @param string $moduleKey Full module key (e.g., vendor.module_name)
     * @param array|null $levels Optional levels
     * @param bool $isCommon Optional is_common flag
     * @return bool
     */
    public static function moduleExists(string $moduleKey, ?array $levels = null, bool $isCommon = true): bool
    {
        $path = self::modulePath($moduleKey, $levels, $isCommon);
        return File::exists($path);
    }
}
