<?php

namespace SchoolPalm\ModuleSDK\Helpers;

use Illuminate\Support\Facades\File;
use SchoolPalm\ModuleSDK\Core\ModuleRegistry;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

class ModuleInstaller
{
    protected array $module = [];
    protected array $createdModules = [];
    protected string $hostRoutesPath = '';
    protected bool $overwrite = true;
    protected ModuleRegistry $registry;

    /**
     * Create a new installer instance (entry point for chaining)
     */
    public static function make(): self
    {
        return new self();
    }

    public function __construct()
    {
        $this->registry = new ModuleRegistry();
        $this->loadCreatedModules();
    }

    /**
     * Load all recorded SDK modules
     */
    public function loadCreatedModules(): self
    {
        $file = base_path('storage/app/sdk_created_modules.json');

        $this->createdModules = file_exists($file)
            ? json_decode(file_get_contents($file), true)
            : [];

        return $this;
    }

    /**
     * Select a module by module_key from created modules
     */
    public function select(string $moduleKey): self
    {
        $module = collect($this->createdModules)
            ->first(fn($m) => $m['module_key'] === $moduleKey);

        if (!$module) {
            throw new \InvalidArgumentException("Module '{$moduleKey}' not found in created modules.");
        }

        $this->module = $module;

        return $this;
    }

    /**
     * Set host routes path and overwrite flag
     */
    public function setHostRoutesPath(string $hostRoutesPath, bool $overwrite = true): self
    {
        $this->hostRoutesPath = $hostRoutesPath;
        $this->overwrite = $overwrite;

        return $this;
    }

    /**
     * Ensure install paths from config exist
     */
    public function ensureInstallPaths(): self
    {
        $paths = config('schoolpalm.install_path', []);

        foreach ($paths as $path) {
            if (!is_string($path) || empty($path)) continue;
            File::ensureDirectoryExists($path);
        }

        return $this;
    }

    /**
     * Install the module to SDK registry
     */
    public function installToRegistry(): self
    {
        if (empty($this->module)) {
            throw new \RuntimeException("No module selected to install.");
        }

        $folder = $this->module['is_common'] ?? false
            ? 'common'
            : ($this->module['levels'][0] ?? 'Default');

        $this->registry->addModule([
            'vendor'    => $this->module['vendor'],
            'module'    => $this->module['module'],
            'name'      => $this->module['module'],
            'folder'    => $folder,
            'module_key'=> $this->module['module_key'],
            'type'      => 'custom',
            'icon'      => $this->module['icon'] ?? null,
            'is_common' => $this->module['is_common'] ?? false,
            'path'      => $this->module['path'],
            'installed' => true,
        ]);

        $this->module['installed'] = true;

        return $this;
    }

    /**
     * Copy vendor routes into app routes folder and auto-require it
     */
    public function installRoutes(): self
    {
        $sourceFile = ModulePaths::basePath('routes/vendor_routes.php');
        $destFile   = base_path('routes/vendor_routes.php');
        $webFile    = base_path('routes/web.php');

        if (File::exists($sourceFile)) {
            File::ensureDirectoryExists(dirname($destFile));

            if (!File::exists($destFile) || md5_file($sourceFile) !== md5_file($destFile)) {
                File::copy($sourceFile, $destFile);
            }

            $this->ensureWebRoutesRequire($webFile);
        }

        // If the selected module has routes
        if (!empty($this->module) && !empty($this->hostRoutesPath)) {
            $modulePath = rtrim($this->module['path'], DIRECTORY_SEPARATOR);
            $sourceFile = $modulePath . DIRECTORY_SEPARATOR . 'vendor_routes.php';
            $destFile   = rtrim($this->hostRoutesPath, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . strtolower($this->module['module_key']) . '_routes.php';

            if (is_file($sourceFile)) {
                File::ensureDirectoryExists($this->hostRoutesPath);
                if (!file_exists($destFile) || $this->overwrite) {
                    copy($sourceFile, $destFile);
                }
            }
        }

        return $this;
    }

    protected function ensureWebRoutesRequire(string $webFile): void
    {
        if (!File::exists($webFile)) return;

        $requireBlock = <<<PHP

// Auto-loaded SchoolPalm vendor routes
\$vendorRoutes = __DIR__ . '/vendor_routes.php';
if (file_exists(\$vendorRoutes)) {
    require \$vendorRoutes;
}

PHP;

        $contents = File::get($webFile);
        if (!str_contains($contents, 'vendor_routes.php')) {
            File::append($webFile, $requireBlock);
        }
    }

    /**
     * Uninstall the selected module
     */
    public function uninstallModule(): self
    {
        if (empty($this->module) || empty($this->hostRoutesPath)) return $this;

        $destFile = rtrim($this->hostRoutesPath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . strtolower($this->module['module_key']) . '_routes.php';

        if (is_file($destFile)) unlink($destFile);

        $this->module['installed'] = false;

        return $this;
    }

    /**
     * Get orphaned modules (static because it doesn't depend on instance)
     */
    public static function getOrphanedModules(array $registry, array $sdkModules): array
    {
        $sdkKeys = array_map(fn($m) => $m['module_key'], $sdkModules);

        return array_filter($registry, fn($m) => !in_array($m['module_key'], $sdkKeys) && ($m['installed'] ?? false));
    }

    /**
     * Get selected module
     */
    public function getModule(): array
    {
        return $this->module;
    }

    /**
     * Get all created modules
     */
    public function getCreatedModules(): array
    {
        return $this->createdModules;
    }
}
