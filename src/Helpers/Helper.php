<?php

namespace SchoolPalm\ModuleSDK\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleSDK\Models\Module;

class Helper
{
    /**
     * Convert a module reference (string, array, or object) into folder name.
     */
    public static function moduleFolderName($module): string
    {
        if (is_string($module)) {
            $moduleKey = $module;
        } elseif (is_array($module) && isset($module['module_key'])) {
            $moduleKey = $module['module_key'];
        } else {
            throw new \InvalidArgumentException('Invalid module parameter provided.');
        }

        $parts = explode('.', $moduleKey);
        if (count($parts) > 1) {
            array_shift($parts);
        }

        return Str::of(implode(' ', $parts))
            ->replace(['.', '_', '-'], ' ')
            ->studly()
            ->toString();
    }

    /**
     * Convert role name to folder-friendly string
     */
    public static function roleFolderName(string $role): string
    {
        return Str::of($role)
            ->replace(['-', '_'], ' ')
            ->title()
            ->replace(' ', '')
            ->toString();
    }

    /**
     * Convert multiple levels into combined folder string
     */
    public static function levelsFolderName(array $levels): string
    {
        if (count($levels) === 1) {
            return Str::studly($levels[0]);
        }

        return implode('', array_map(
            fn($levelName) => config('school.academic_level_codes')[$levelName] ?? substr($levelName, 0, 3),
            $levels
        ));
    }

    /**
     * Check if a specific school level exists in a combined levels folder string
     */
    public static function levelExists(string $level, string $folderName): bool
    {
        $levelCodes = config('school.academic_level_codes', []);
        if (!isset($levelCodes[$level])) {
            return false;
        }
        return Str::contains($folderName, $levelCodes[$level]);
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
        return config('school.academic_level')[$index] ?? null;
    }


    public static function loadJson(string $file): ?array
    {
        if (!File::exists($file)) return null;
        $content = File::get($file);
        return json_decode($content, true);
    }



    public static   function getRouteSegment(string $key, bool $ref = false, $path = null): ?string
    {
        $request = request();

        // Get segments array
        if ($ref) {
            $referer = $request->headers->get('referer');
            if ($referer) {
                // Parse path and split into segments
                $segments = array_values(array_filter(explode('/', parse_url($referer, PHP_URL_PATH))));
            } else {
                $segments = [];
            }
        } else {
            $segments = $request->segments();
        }

        if ($path)
            $segments = explode('/', trim($path, '/'));

        // Map keys to segment index
        $map = ['portal' => 0, 'module' => 1, 'action' => 2, 'id' => 3];

        if (!isset($map[$key])) {
            return null;
        }

        return $segments[$map[$key]] ?? null;
    }


  public static  function normalizeModuleName(string $module): ?string
{
    if (str_contains($module, '-'))
        $module = preg_replace('/-+/', ' ', $module);
    return Str::studly($module);
}
}
