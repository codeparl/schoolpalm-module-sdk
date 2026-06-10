<?php

namespace SchoolPalm\ModuleSDK\Manifest;

use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Support\Helper as SupportHelper;
use SchoolPalm\ModuleSDK\Helpers\Helper;

class ManifestFactory
{
    public static function make(array $data): array
    {
        $config = config('sdk');

        $name        = $data['name'];
        $vendor      = $data['vendor'] ?? $config['vendor'] ?? 'SchoolPalm';
        $moduleKey   = $data['module_key'] ?? strtolower($vendor) . '.' . self::normalizePermission($name);
        $role        = $data['role'] ?? $config['defaults']['role'] ?? 'admin';
        $version     = $data['version'] ?? $config['defaults']['version'] ?? '1.0.0';
        $type        = $data['type'] ?? $config['defaults']['type'] ?? 'external';
        $description = $data['description'] ?? '';
        $isCommon    = $data['is_common'] ?? true;
        $level       = $data['level'] ?? [];

        if ($isCommon && !empty($level)) $isCommon = false;

        if (!$isCommon && empty($level)) $level = [0];



        $moduleName   = Helper::moduleFolderName($name);
        $root_np = Helper::moduleNamespace($name, $level);
        $namespace    = $root_np . '\\Backend';
        $authorName  =  is_array($data['author'])  ? $data['author']['name'] : $data['author'];
        $author = [
            'name'    => $authorName ?? $config['author']['name'] ?? 'SchoolPalm',
            'email'   => $data['author']['email'] ?? $config['author']['email'] ?? null,
            'website' => $data['author']['website'] ?? $config['author']['website'] ?? null,
        ];

        $dependencies = [
            'backend'  => (object) ($data['dependencies']['backend']  ?? $config['dependencies']['backend']  ?? []),
        ];



        $migrations = [
            'path'           => $data['migrations']['path'] ?? 'Database/migrations',
            'run_on_install' => $data['migrations']['run_on_install'] ?? true,
            'run_on_update'  => $data['migrations']['run_on_update'] ?? true,
            'tables' => $data['tables'] ?? []
        ];



        $models = [
            'path'      => $data['models']['path'] ?? 'Models',
            'namespace' => $namespace . '\\Models',
            'autoload'  => $data['models']['autoload'] ?? true,
        ];

        $menus = $data['menus'] ?? [[
            'name'        => $moduleKey . '.' . ($config['menu']['permission'] ?? 'manage') . '.' . self::normalizePermission($name),
            'label'       => ucfirst($name),
            'icon'        => $data['icon'] ?? 'lucide-layers',
            'permission'  => ($config['menu']['permission'] ?? 'manage') . '.' . self::normalizePermission($name),
            'route'       => $config['menu']['route'] ?? null,
            'description' => $description,
            'children'    => [],
        ]];

        $actions = $data['actions'] ?? [];

        $entry = $data['entry'] ?? [
            'provider' => $namespace . '\\Providers\\' . self::generateModuleServiceProvider($moduleKey)

        ];

        $provides = [];
        if (is_array($data['provides'] ?? []) && count($data['provides']) > 0) {
            foreach ($data['provides'] as $key => $contract) {
                array_push($provides, $namespace . '\\Contracts\\' . $contract);
            }
        } else {
            // Derive default provides if not supplied
            $provides = $data['provides'] ?? [$namespace . '\\Contracts\\' . Str::singular($moduleName) . 'Contract'];
        }



        // Keep events empty if not supplied
        $events = self::generateEvents($namespace, Str::singular($moduleName))['events'];
        $sdk = ['name' => 'schoolpalm/module-sdk', 'version' => '1.5.0'];
        return [
            'name'         => $name,
            'namespace' => $namespace,
            'vendor'       => $vendor,
            'prefix' => $data['prefix'] ?? self::prefix($moduleKey),
            'module_key'   => $moduleKey,
            'description'  => $description,
            'version'      => $version,
            'icon'      => $data['icon'] ?? 'lucide-Layers',
            'type'         => $type,
            'sdk'          => $sdk,
            'menus'        => $menus,
            'role'         => $role,
            'actions'      => $actions,
            'entry'        => $entry,
            'author'       => $author,
            'dependencies' => (object) $dependencies,
            'level'        => $data['level'] ?? [],
            'is_common'    => $isCommon,
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


    public static function generateModuleServiceProvider(string $key): string
    {
        return collect(explode('.', $key))
            ->map(fn($part) => Str::studly($part))
            ->implode('') . 'ServiceProvider';
    }

    public static function prefix(string $key): string
    {
        return  config('sdk.prefix') ??   implode('', array_map(
            fn($part) => $part[0] ?? '',
            explode('.', $key)
        ));
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
        return SupportHelper::loadJson($filePath);
    }


    public static function update($existingManifest, array $data, string $filePath)
    {

        // 2. Merge allowed editable fields
        $updatedManifest = array_merge($existingManifest, [
            'name'         => $data['name']         ?? $existingManifest['name'],
            'description'  => $data['description']  ?? $existingManifest['description'],
            'version'      => $data['version']      ?? $existingManifest['version'],
            'icon'       => $data['icon'] ?? 'lucide-Layers',
            'prefix' => $data['prefix'] ?? self::prefix($data['module_key']),
            'menus'   => $data['menus']   ?? $existingManifest['menus'],
            'provides'   => $data['provides']   ?? [],
            'migrations' => $data['migrations'],
            'actions' => $data['actions'] ?? $existingManifest['actions']
        ]);

        ManifestValidator::validate($updatedManifest);

        SupportHelper::storeJson($filePath, $updatedManifest);
    }
}
