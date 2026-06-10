<?php

namespace SchoolPalm\ModuleSDK\core;

use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Context\CurrentContext;
use SchoolPalm\ModuleBridge\Contracts\ResolverContract;
use SchoolPalm\ModuleBridge\Facades\CreatedRegistry;

class ModuleResolver implements ResolverContract
{

protected array $registry;
public array $module;
    public function __construct(protected string  $module_key)
    {

        $this->registry =  CreatedRegistry::all();
        $this->module  =  CreatedRegistry::get($module_key);

        //module found then set its context
        if($this->module)
        CurrentContext::set($this->module['module_key']);

    }

    /* -------------------------------------------------------------
     | Registry
     | -------------------------------------------------------------
     */

    public function getModuleData(): ?array
    {

        $module_key = Str::lower($this->module_key);

        foreach ($this->registry as $entry) {
            if (Str::endsWith($entry['module_key'], ".$module_key")) {
                return $entry;
            }
        }

        return null;
    }

    protected function requireModule(): array
    {
        $data = $this->getModuleData($this->module_key);

        if (!$data) {
            abort(404, "Module {$this->module_key} not found in registry.");
        }

        return $data;
    }

    /* -------------------------------------------------------------
     | Main Class
     | -------------------------------------------------------------
     */

    public function resolveModuleMainClass(): string
    {
        $data = $this->requireModule($this->module_key);

        $class = $data['namespace'] . '\\ModuleActionEntry';

        if (!class_exists($class)) {
            abort(404, "ModuleActionEntry class not found for module: {$class}");
        }

        return $class;
    }

    /* -------------------------------------------------------------
     | Actions
     | -------------------------------------------------------------
     */

    public function resolveActionNamespace(): string
    {
        $data = $this->requireModule($this->module_key);
        return $data['namespace'] . '\\Actions';
    }

    public function resolveActionClass(string $action): string
    {
        $namespace = $this->resolveActionNamespace($this->module_key);
        $action = Str::studly($action);

        $class = "{$namespace}\\{$action}Action";

        if (!class_exists($class)) {
            abort(404, "Action {$action} not found in module {$this->module_key}");
        }

        return $class;
    }

    public function resolveActionPath(): string
    {
        $data = $this->requireModule($this->module_key);
        return rtrim($data['path'], '/') . '/Actions';
    }

    /* -------------------------------------------------------------
     | Utilities
     | -------------------------------------------------------------
     */

    public function resolveModulePath(): string
    {
        $data = $this->requireModule($this->module_key);
        return rtrim($data['path'], '/');
    }

    public function resolveModuleNamespace(): string
    {
        $data = $this->requireModule($this->module_key);
        return $data['namespace'];
    }
}
