<?php

namespace SchoolPalm\ModuleSDK\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Support\Helper as BridgeHelper;
use SchoolPalm\ModuleSDK\Models\Module;

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
    // Handle common module
    if (empty($levels) || in_array(0, $levels, true)) {
        return 'Common';
    }

    // Single level → StudlyCase label OR code
    if (count($levels) === 1) {
        $level = AcademicLevelManager::getByNumber((int) $levels[0]);

        return $level
            ? Str::studly($level['code'])
            : 'Common';
    }

    // Multiple levels → Join by codes
    return AcademicLevelManager::joinByCodes(
        array_map('intval', $levels)
    );
}


    /**
     * Check if a specific school level exists in a combined levels folder string
     */
    public static function levelExists(string $level, string $folderName): bool
    {
        return AcademicLevelManager::levelExists($level,$folderName);
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
        return AcademicLevelManager::getByNumber($index)['code'];
    }


    public static function loadJson(string $file): ?array
    {
        return BridgeHelper::loadJson($file);
    }



    public static   function getRouteSegment(string $key, bool $ref = false, $path = null): ?string
    {
        return BridgeHelper::getPathSegment($key,$ref);
    }


  public static  function normalizeModuleName(string $module): ?string
{
  return   BridgeHelper::normalizeModuleName($module);
}
}
