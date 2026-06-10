<?php

namespace SchoolPalm\ModuleSDK\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Support\Helper as BridgeHelper;
use SchoolPalm\ModuleBridge\Support\LevelManager;
use SchoolPalm\ModuleSDK\ModuleSDKServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class Helper
{
    /**
     * Convert a module reference (string, array, or object) into folder name.
     */
    public static function moduleFolderName($module): string
    {
        return BridgeHelper::moduleFolderName($module);
    }

    

    /**
     * Convert role name to folder-friendly string
     */
    public static function roleFolderName(string $role): string
    {
        return BridgeHelper::roleFolderName($role);
    }

    /**
     * Convert multiple levels into combined folder string
     */

    public static function levelsFolderName(array $levels): string
    {
        return LevelManager::level(ModuleSDKServiceProvider::$academicLevels)->joinByCodes($levels);
    }


    /**
     * Check if a specific school level exists in a combined levels folder string
     */
    public static function levelExists(string $level, string $folderName): bool
    {
        return LevelManager::level(ModuleSDKServiceProvider::$academicLevels)->levelExists($level, $folderName);
    }

    /**
     * Generate the PHP namespace for a module
     */
    public static function moduleNamespace($module, string|null $level = null): string
    {
        $moduleName = Str::studly(self::moduleFolderName($module));
        $vendorName = Str::studly($module->vendor);

        $levels = ($module->is_common ?? false)
            ? ['Common']
            : ($module->settings['level'] ?? ['Common']);

        $levelFolder = $module->is_common
            ? $levels[0]
            : ($level ?? self::levelsFolderName($levels));

        return "{$vendorName}\\{$levelFolder}\\{$moduleName}";
    }

    /**
     * Get level by index/key
     */
    public static function levelByKey($index)
    {
        return LevelManager::level(ModuleSDKServiceProvider::$academicLevels)->getByNumber($index)['code'];
    }


    public static function loadJson(string $file): ?array
    {

        return BridgeHelper::loadJson($file);
    }

 public static function storeJson(string $filePath, array $data):bool
    {
        return BridgeHelper::storeJson($filePath, $data);
    }

    public static   function getRouteSegment(string $key,  $path = null,string $mode = 'full'): ?string
    {
        $path  =  $path ? $path: implode('/',request()->segments());
        return  BridgeHelper::getPathSegment($key, $path, $mode);
    }


    public static  function normalizeModuleName(string $module): ?string
    {
        return   BridgeHelper::normalizeModuleName($module);
    }
}
