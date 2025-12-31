<?php

namespace SchoolPalm\ModuleSDK\Core;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

/**
 * Class ModuleRegistry
 *
 * Handles loading, caching, and management of modules from the registry.
 */
class ModuleRegistry
{
    /** @var string Path to modules.json */
    protected string $registryPath;

    /** @var array<string, array> Loaded modules (cached) */
    protected array $modules = [];

    public function __construct()
    {
        $this->registryPath = ModulePaths::registryFile();
        $this->load();
    }

    /**
     * Load and cache modules
     */
    protected function load(): void
    {
        if (!File::exists($this->registryPath)) {
            throw new \RuntimeException("Module registry not found at {$this->registryPath}");
        }

        $this->modules = Cache::remember(
            'schoolpalm.modules.registry',
            now()->addMinutes(10),
            fn () => $this->parse()
        );
    }

    /**
     * Parse registry JSON into normalized array
     */
    protected function parse(): array
    {
        $data = json_decode(File::get($this->registryPath), true, flags: JSON_THROW_ON_ERROR);

        return collect($data)
            ->map(fn ($entry) => $this->normalize($entry))
            ->keyBy('module_key')
            ->toArray();
    }

    /**
     * Normalize a single module entry
     */
    protected function normalize(array $entry): array
    {
        foreach (['module_key', 'namespace', 'path'] as $key) {
            if (empty($entry[$key])) {
                throw new \InvalidArgumentException("Invalid module registry entry: missing {$key}");
            }
        }

        [$vendor, $module] = explode('.', $entry['module_key'], 2);

        return [
            'id' => $entry['id'],
            'module_key' => Str::lower($entry['module_key']),
            'vendor'     => Str::studly($vendor),
            'module'     => Str::studly($module),
            'role'       => Str::studly($entry['role'] ?? 'admin'),
            'namespace'  => trim($entry['namespace'], '\\'),
            'path'       => rtrim($entry['path'], DIRECTORY_SEPARATOR),
            'manifest'   => $entry['manifest'] ?? null,
        ];
    }

    /* ---------------- Instance API ---------------- */

    /** @return array<string, array> */
    public function all(): array
    {
        return $this->modules;
    }

    /** @return array|null */
    public function get(string $module): ?array
    {
        $key = Str::lower($module);

        return $this->modules[$key]
            ?? collect($this->modules)->first(fn ($m) => $m['module'] === Str::studly($module));
    }

    /** @return array */
    public function require(string $module): array
    {
        return $this->get($module) ?? throw new \RuntimeException("Module {$module} not registered.");
    }

    /** Clear the cached registry */
    public function clearCache(): void
    {
        Cache::forget('schoolpalm.modules.registry');
        $this->load();
    }

    /* ---------------- Registry modification ---------------- */

    /** Register a new module */
    public function register(array $moduleData): void
    {
        dump($moduleData, $this->registryPath);
        $modules = array_values($this->modules);

        if ($this->exists($moduleData['module_key'])) return;

        $moduleData['id']         = count($modules) + 1;
        $moduleData['created_at'] = now()->toDateTimeString();

        $modules[] = $moduleData;

        File::put(
            $this->registryPath,
            json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->clearCache();
    }

    /** Remove a module by ID */
    public function remove(int $id): void
    {
        $modules = array_filter($this->modules, fn ($m) => ($m['id'] ?? 0) !== $id);
        $modules = array_values($modules);

        foreach ($modules as $index => &$module) {
            $module['id'] = $index + 1;
        }

        File::put(
            $this->registryPath,
            json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->clearCache();
    }

    /** Check if module exists by module_key */
    public function exists(string $moduleKey): bool
    {
        foreach ($this->modules as $m) {
            if (($m['module_key'] ?? null) === $moduleKey) return true;
        }
        return false;
    }

    /** Find a module by ID */
    public function find(int $id): ?array
    {
        foreach ($this->modules as $m) {
            if (($m['id'] ?? 0) === $id) return $m;
        }
        return null;
    }

    /** CLI-friendly list (1-based) */
    public function listForCLI(): array
    {
        $choices = [];
        foreach ($this->modules as $i => $m) {
            $choices[(int)$i + 1] = $m['namespace'];
        }
        return $choices;
    }
}
