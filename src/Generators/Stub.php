<?php

namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Support\Helper;
use SchoolPalm\ModuleSDK\Console\ModuleCommandBase;
use SchoolPalm\ModuleSDK\Support\ModulePaths;
use SchoolPalm\ModuleBridge\Facades\CreatedRegistry;
use SchoolPalm\ModuleBridge\Manifest\ModuleManifest;

abstract class Stub
{
    protected array $paths;
    protected array $module;
    protected string $rootPath;
    protected array $stubMap;
    protected string $stubPath;
    protected string $stubRootPath;
    protected ?ModuleCommandBase $cmd;
    protected ModuleManifest $manifest;

    public function __construct(private string $module_key, $cmd = null)
    {

        $this->cmd = $cmd;
        $this->module = CreatedRegistry::get($module_key);
        $this->rootPath = ModulePaths::modulePath($this->module['root']) ;
        $this->stubPath = ModulePaths::stubsPath();
        $this->stubRootPath = $this->stubPath;

        $this->paths  =  Helper::loadJson($this->rootPath . '/module_structure.json')['paths']['frontend'] ?? [];
        $this->stubMap = Helper::loadJson(ModulePaths::stubsMap());
        $this->module['name'] =  Str::studly(Str::slug($this->module['module'], ' '));
        $this->manifest  =  new ModuleManifest($this->module['manifest']);
    }

    protected function only(?array $keys = [])
{
    $module = $this->module;

    $data = empty($keys)
        ? $module
        : collect($module)
            ->only($keys)
            ->toArray();

    // remove last segment of path
    $data['path'] =  Str::beforeLast($data['path'], DIRECTORY_SEPARATOR);
  

    return $data;
}

public function publishStub(string $stubPath, string $targetPath, array $moduleData = []): void
{
    // Remove .stub extension
    $targetPath = Str::beforeLast($targetPath, '.stub');

    // Ensure stub exists
    if (!File::exists($stubPath)) {
        throw new \RuntimeException("Stub missing: {$stubPath}");
    }

    // Load stub
    $content = File::get($stubPath);

    // Normalize module key: vendor.context.module
    $key = strtolower($this->module['module_key'] ?? '');
    $segments = array_filter(explode('.', $key));

    $vendor = array_shift($segments) ?? '';

    // Build naming formats
    $dashName = implode('-', $segments); 
    $underscoreName = implode('_', $segments); 

    // Base data
    $baseData = [
        'module'        => $this->module['module'] ?? '',
        'moduleName'        => strtolower($this->module['module']) ?? '',
        'singular'      => Str::singular($this->module['module'] ?? ''),
        'module_key'    => $key,
        'package_name'  => '@' . $vendor . ($dashName ? '/' . $dashName : ''),
        'port'          => $this->module['dev_port'] ?? '',
        'vendor'        => Str::studly($vendor),
          'context'=>$this->module['context'],
        // ✅ underscore-only moduleId
        'moduleId'      => Str::lower(
            $vendor . ($underscoreName ? '_' . $underscoreName : '') . '_app'
        ),

        'namespace'     => $this->module['namespace'] ?? '',
    ];

    // Merge overrides
    $data = array_merge($baseData, $moduleData);

    // Replace placeholders: {{ key }}
    $content = preg_replace_callback('/{{\s*(\w+)\s*}}/', function ($matches) use ($data) {
        return $data[$matches[1]] ?? $matches[0];
    }, $content);

    // Ensure directory exists
    $dir = dirname($targetPath);
    if (!File::isDirectory($dir)) {
        File::makeDirectory($dir, 0755, true, true);
    }

    // Write file
    File::put($targetPath, $content);

    // Console feedback
    if ($this->cmd) {
        $this->cmd->info("Created stub for {$baseData['module']}: {$targetPath}");
    }
}
}
