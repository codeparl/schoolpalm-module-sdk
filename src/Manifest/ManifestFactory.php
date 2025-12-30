<?php

namespace SchoolPalm\ModuleSDK\Manifest;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use SchoolPalm\ModuleSDK\Helpers\Helper;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

class ManifestFactory
{
   public static function make(array $data): array
    {
        $config = config('schoolpalm');

        $name        = $data['name'];
        $vendor      = $data['vendor'] ?? $config['vendor'] ?? 'SchoolPalm';
        $moduleKey   = $data['module_key'] ?? strtolower($vendor) . '.' . self::normalizePermission($name);
        $role        = $data['role'] ?? $config['defaults']['role'] ?? 'admin';
        $version     = $data['version'] ?? $config['defaults']['version'] ?? '1.0.0';
        $type        = $data['type'] ?? $config['defaults']['type'] ?? 'external';
        $description = $data['description'] ?? '';
        $isCommon    = $data['is_common'] ?? true;
        $level       = $data['level'] ?? [];

        if (!$isCommon && empty($level)) $level = [0];

        $levelSegment = Helper::levelsFolderName($level);
        $moduleName   = Helper::moduleFolderName($name);
        $namespace    = Str::studly($vendor) . '\\' . $levelSegment . '\\' . $moduleName;

        $author = [
            'name'    => $data['author']['name'] ?? $config['author']['name'] ?? 'SchoolPalm',
            'email'   => $data['author']['email'] ?? $config['author']['email'] ?? null,
            'website' => $data['author']['website'] ?? $config['author']['website'] ?? null,
        ];

           $dependencies = [
        'backend'  => (object) ($data['dependencies']['backend']  ?? $config['dependencies']['backend']  ?? []),
        'frontend' => (object) ($data['dependencies']['frontend'] ?? $config['dependencies']['frontend'] ?? []),
    ];

        $resources = [
            'ui' => [
                'framework'   => $data['resources']['ui']['framework']   ?? $config['resources']['ui']['framework'] ?? [],
                'tailwind'    => $data['resources']['ui']['tailwind']    ?? $config['resources']['ui']['tailwind'] ?? false,
                'source_path' => $data['resources']['ui']['source_path'] ?? $config['resources']['ui']['source_path'] ?? null,
            ],
            'assets' => $data['resources']['assets'] ?? $config['resources']['assets'] ?? null,
        ];

        $migrations = [
            'path'           => $data['migrations']['path'] ?? 'database/migrations',
            'run_on_install' => $data['migrations']['run_on_install'] ?? true,
            'run_on_update'  => $data['migrations']['run_on_update'] ?? true,
        ];

        $models = [
            'path'      => $data['models']['path'] ?? 'Models',
            'namespace' => $namespace . '\\Models',
            'autoload'  => $data['models']['autoload'] ?? true,
        ];

        $menus = $data['menus'] ?? [[
            'name'        => $moduleKey . '.' . ($config['menu']['permission'] ?? 'manage') . '.' . self::normalizePermission($name),
            'label'       => $name,
            'icon'        => $data['icon'] ?? 'lucide-layers',
            'permission'  => ($config['menu']['permission'] ?? 'manage') . '.' . self::normalizePermission($name),
            'route'       => $config['menu']['route'] ?? null,
            'description' => $description,
            'children'    => [],
        ]];

        $actions = $data['actions'] ?? [];

        $entry = $data['entry'] ?? [
            'provider' => $namespace . '\\Providers\\' . Str::singular($moduleName ). 'ServiceProvider',
        ];

        // Derive default provides if not supplied
        $provides = $data['provides'] ?? [$namespace . '\\Contracts\\' . Str::singular($moduleName ) . 'Contract'];

        // Keep events empty if not supplied
        $events = self::generateEvents($namespace, Str::singular($moduleName ))['events'];

        return [
            'name'         => $name,
            'vendor'       => $vendor,
            'module_key'   => $moduleKey,
            'description'  => $description,
            'version'      => $version,
            'type'         => $type,
            'menus'        => $menus,
            'role'         => $role,
            'actions'      => $actions,
            'entry'        => $entry,
            'author'       => $author,
            'dependencies' => (object) $dependencies,
            'level'        => $data['level'] ?? [],
            'is_common'    => $isCommon,
            'resources'    => $resources,
            'migrations'   => $migrations,
            'models'       => $models,
            'events'       => $events,
            'provides'     => $provides,
            'requires'     => $data['requires'] ?? ['modules' => new \stdClass()],
        ];
    }

    /**
     * Generate default events and listeners for a module
     *
     * @param string $namespace Base namespace for the module (e.g., Vendor\Level\Module)
     * @param string $moduleName Module name (e.g., "Student")
     * @return array List of fully qualified class names for events and listeners
     */
    public static function generateEvents(string $namespace, string $moduleName): array
    {
        // Default event actions
        $baseEvents = [
            'Created',
            'Updated',
            'Deleted',
        ];

        // Generate event FQCNs under namespace\Events\ModuleEvent
        $events = array_map(fn($e) => $namespace . '\\Events\\' . $moduleName . $e, $baseEvents);

        // Generate listener FQCNs under namespace\Listeners\ModuleEventListener
        $listeners = array_map(fn($e) => $namespace . '\\Listeners\\' . $moduleName . $e . 'Listener', $baseEvents);

        return [
            'events'    => $events,
            'listeners' => $listeners,
        ];
    }

public static function normalizeJson(array &$data, array $schema, string $path = ''): void
{
    if (!isset($schema['properties']) || !is_array($schema['properties'])) {
        return;
    }

    foreach ($schema['properties'] as $key => $propertySchema) {
        $currentPath = $path === '' ? $key : "{$path}.{$key}";
        $valueExists = array_key_exists($key, $data);
        $value = $valueExists ? $data[$key] : null;

        // --------------------------------------------------
        // CASE 1: OBJECT normalization
        // --------------------------------------------------
        if (($propertySchema['type'] ?? null) === 'object') {
            $hasRequiredProps = isset($propertySchema['required']);
            $isMapObject = isset($propertySchema['additionalProperties']);

            // Normalize missing value
            if (!$valueExists) {
                if (isset($propertySchema['default'])) {
                    $data[$key] = $propertySchema['default'];
                } elseif ($isMapObject && !$hasRequiredProps) {
                    $data[$key] = (object)[];
                }
            }

            // Normalize [] → {}
            if ($valueExists && is_array($value) && empty($value) && $isMapObject) {
                $data[$key] = (object)[];
            }

            // Recurse if object now exists
            if (isset($data[$key]) && is_array($data[$key])) {
                self::normalizeJson($data[$key], $propertySchema, $currentPath);
            }

            if (isset($data[$key]) && is_object($data[$key])) {
                // Convert object to array temporarily for recursion
                $tmp = (array) $data[$key];
                self::normalizeJson($tmp, $propertySchema, $currentPath);
                $data[$key] = (object) $tmp;
            }
        }

        // --------------------------------------------------
        // CASE 2: ARRAY recursion (items)
        // --------------------------------------------------
        if (($propertySchema['type'] ?? null) === 'array' && isset($propertySchema['items']) && $valueExists && is_array($value)) {
            foreach ($value as $index => $item) {
                if (is_array($item) && isset($propertySchema['items']['properties'])) {
                    self::normalizeJson($data[$key][$index], $propertySchema['items'], "{$currentPath}[{$index}]");
                }
            }
        }
    }
}


    protected static function normalizePermission(string $name): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '.', trim($name)));
    }



    /**
 * Load a module manifest JSON file
 *
 * @param string $filePath Full path to manifest.json
 * @return array|null Returns associative array on success, null on failure
 */
public  static function  loadManifest(string $filePath): ?array
{

    if (!file_exists($filePath)) {
        return null;
    }

    $jsonContent = file_get_contents($filePath);
    if (!$jsonContent) {
        // Empty file or read error
        return null;
    }

    $data = json_decode($jsonContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // JSON parsing failed
        return null;
    }

    return $data;
}


}
