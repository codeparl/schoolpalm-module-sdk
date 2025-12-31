<?php

namespace SchoolPalm\ModuleSDK\Helpers;

use Illuminate\Support\Facades\File;

class ModuleInstaller
{
    /**
     * Install a module into the host Laravel app.
     *
     * Copies the module's vendor routes file into the host app's routes folder.
     *
     * @param string $moduleName The module folder name inside SDK (e.g., 'Students')
     * @param string|null $sourceBase Optional: base path of SDK modules (default: base_path('modules'))
     * @param string|null $destBase Optional: base path in host app (default: base_path('routes'))
     *
     * @return bool True if file was copied successfully, false otherwise
     */
    public static function installRoutes(string $moduleName, ?string $sourceBase = null, ?string $destBase = null): bool
    {
        $sourceBase = $sourceBase ?? base_path('modules');
        $destBase   = $destBase ?? base_path('routes');

        $sourceFile = rtrim($sourceBase, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $moduleName
            . DIRECTORY_SEPARATOR
            . 'vendor_routes.php';

        if (! File::exists($sourceFile)) {
            return false;
        }

        $destFile = rtrim($destBase, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . strtolower($moduleName) . '_routes.php';

        // Ensure destination folder exists
        if (! File::isDirectory($destBase)) {
            File::makeDirectory($destBase, 0777, true);
        }

        return File::copy($sourceFile, $destFile);
    }



       /**
     * Install a module into the host Laravel app.
     *
     * Copies the module's vendor routes file into the host app's routes folder
     * and updates the registry flag `installed: true`.
     *
     * @param array  $module         Module registry item
     * @param string $hostRoutesPath Host app routes folder
     *
     * @return bool True on success, false otherwise
     */
   public static function installModule(array &$module, string $hostRoutesPath, bool $overwrite = true): bool
{
    $modulePath = rtrim($module['path'], DIRECTORY_SEPARATOR);
    $sourceFile = $modulePath . DIRECTORY_SEPARATOR . 'vendor_routes.php';

    if (! is_file($sourceFile)) {
        return false;
    }

    $destFile = rtrim($hostRoutesPath, DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR
        . strtolower($module['module_key']) . '_routes.php';

    if (! is_dir($hostRoutesPath) && ! mkdir($hostRoutesPath, 0755, true) && ! is_dir($hostRoutesPath)) {
        return false;
    }

    if (file_exists($destFile) && ! $overwrite) {
        return true; // already installed, skip
    }

    if (! copy($sourceFile, $destFile)) {
        return false;
    }

    $module['installed'] = true;
    return true;
}


    /**
     * Uninstall a module from the host Laravel app.
     *
     * Removes the copied routes file and updates the registry flag `installed: false`.
     *
     * @param array  $module         Module registry item
     * @param string $hostRoutesPath Host app routes folder
     *
     * @return bool True if file removed successfully or does not exist, false otherwise
     */
    public static function uninstallModule(array &$module, string $hostRoutesPath): bool
    {
        $destFile = rtrim($hostRoutesPath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . strtolower($module['module_key']) . '_routes.php';

        if (is_file($destFile)) {
            if (! unlink($destFile)) {
                return false;
            }
        }

        $module['installed'] = false;
        return true;
    }

    /**
     * Check registry for modules that are installed but removed from SDK.
     *
     * Returns a list of orphaned modules that exist in host app but not in current SDK.
     *
     * @param array $registry   Full module registry
     * @param array $sdkModules Modules currently available in SDK
     *
     * @return array List of orphaned modules
     */
    public static function getOrphanedModules(array $registry, array $sdkModules): array
    {
        $sdkKeys = array_map(fn($m) => $m['module_key'], $sdkModules);

        return array_filter($registry, fn($m) => ! in_array($m['module_key'], $sdkKeys) && ($m['installed'] ?? false));
    }
}
