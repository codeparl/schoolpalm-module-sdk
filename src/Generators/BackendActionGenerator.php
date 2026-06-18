<?php

namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Services\DtoReflectionService;
use SchoolPalm\ModuleBridge\Manifest\ModuleManifest;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

class BackendActionGenerator
{
    protected string $actionsFile;
    protected string $stubBasePath;
    protected string $rootPath;

    public function __construct(
        ?string $actionsFile,
        protected array $action,
        protected string $type,
        protected string $dto,
        protected DtoReflectionService $dtoReflection,
        protected ModuleManifest $manifest,
        ?string $stubBasePath = null
    ) {
        $this->rootPath = ModulePaths::modulePath($this->manifest->root());
        $this->actionsFile = $actionsFile ?? $this->resolveActionsFilePath();
        $this->stubBasePath = $stubBasePath ?? $this->getDefaultStubPath();

        // Ensure the Actions class file exists before any generation
        $this->ensureActionsFileExists();
    }

    protected function resolveActionsFilePath(): string
    {
        $backendPath = $this->rootPath . '/Backend';
        $className = Str::studly($this->manifest->info()->name()) . 'Actions';
        return $backendPath . '/Actions/' . $className . '.php';
    }

    protected function getDefaultStubPath(): string
    {
        return ModulePaths::stubsPath();
    }

    /**
     * Create the Actions class file from stub if it doesn't exist.
     */
    protected function ensureActionsFileExists(): void
    {
        if (File::exists($this->actionsFile)) {
            return;
        }

        $stubPath = $this->stubBasePath . '/backend/Actions.php.stub';
        if (!File::exists($stubPath)) {
            throw new \RuntimeException("Actions stub missing: {$stubPath}");
        }

        $content = File::get($stubPath);
        $className = Str::studly($this->manifest->info()->name()) . 'Actions';
        $namespace = $this->manifest->info()->namespace() . '\\Actions';

        $replacements = [
            'namespace' => $namespace,
            'class'     => $className,
        ];

        $content = preg_replace_callback('/{{\s*(\w+)\s*}}/', function ($matches) use ($replacements) {
            return $replacements[$matches[1]] ?? $matches[0];
        }, $content);

        File::ensureDirectoryExists(dirname($this->actionsFile));
        File::put($this->actionsFile, $content);
    }

    protected function getModuleData(): array
    {
        $key = $this->manifest->key();
        $segments = explode('.', $key);
        $vendor = $segments[0] ?? '';
        $moduleName = $segments[1] ?? '';

        return [
            'module_key'   => $key,
            'vendor'       => Str::studly($vendor),
            'module'       => Str::studly($moduleName),
            'namespace'    => $this->manifest->info()->namespace(),
            'package_name' => '@' . $vendor . '/' . $moduleName,
        ];
    }

    public function generate(): void
    {
        // Map 'create' to store, 'edit' to update for contract selection
        $effectiveType = match ($this->type) {
            'create' => 'store',
            'edit'   => 'update',
            default  => $this->type
        };
        $useCore = true; #in_array($effectiveType, ['store', 'update', 'delete']);
        $contract = $this->dtoReflection->resolveContract($this->dto, $useCore);
        $methodName = 'run' . Str::studly($this->extractActionName());

        if ($this->methodExists($methodName)) {
            return;
        }

        $stub = match ($this->type) {
            'list'           => $this->buildListMethod($methodName, $contract),
            'view'           => $this->buildViewMethod($methodName, $contract),
            'store', 'create'=> $this->buildStoreMethod($methodName, $contract),
            'update', 'edit' => $this->buildUpdateMethod($methodName, $contract),
            'delete'         => $this->buildDeleteMethod($methodName, $contract),
            default          => throw new \RuntimeException("Unsupported backend action type [{$this->type}]")
        };

        $this->addUseStatement($contract['fqcn']);
        $this->injectMethod($stub);
    }

    protected function extractActionName(): string
    {
        $route = $this->action['route'] ?? '';
        // Remove trailing /{id} or /:id to get clean action name
        $cleanRoute = preg_replace('/\/(?:\{id\}|:id)$/', '', $route);
        return trim(last(explode('/', trim($cleanRoute, '/'))));
    }

    protected function methodExists(string $method): bool
    {
        if (!File::exists($this->actionsFile)) {
            return false;
        }
        return str_contains(File::get($this->actionsFile), "function {$method}(");
    }

    /* =========================================================
     * METHOD BUILDERS
     * ========================================================= */

    protected function buildListMethod(string $method, array $contract): string
    {
        $data = $this->getModuleData();
        $actionName = $this->extractActionName();
        return <<<PHP

    /**
     * List all {$this->dto} resources.
     *
     * @generated
     * @module-action {$actionName}
     * @module-key {$data['module_key']}
     * @return \Illuminate\Http\JsonResponse
     */
    public function {$method}(
        {$contract['class']} \${$contract['variable']}
    )
    {
        return response()->json(
            \${$contract['variable']}->all()
        );
    }

PHP;
    }

    protected function buildViewMethod(string $method, array $contract): string
    {
        $data = $this->getModuleData();
        $actionName = $this->extractActionName();
        return <<<PHP

    /**
     * Retrieve a single {$this->dto} resource by ID.
     *
     * @generated
     * @module-action {$actionName}
     * @module-key {$data['module_key']}
     * @param int|string \$id
     * @return \Illuminate\Http\JsonResponse
     */
    public function {$method}(
        int|string \$id,
        {$contract['class']} \${$contract['variable']}
    )
    {
        return response()->json(
            \${$contract['variable']}->find(\$id)
        );
    }

PHP;
    }

    protected function buildStoreMethod(string $method, array $contract): string
    {
        $data = $this->getModuleData();
        $actionName = $this->extractActionName();
        return <<<PHP

    /**
     * Create a new {$this->dto} resource.
     *
     * Payload is accessed via `request()->all()`.
     *
     * @generated
     * @module-action {$actionName}
     * @module-key {$data['module_key']}
     * @return \Illuminate\Http\JsonResponse
     */
    public function {$method}(
        {$contract['class']} \${$contract['variable']}
    )
    {
        try {
            \$data = request()->all();
            \$result = \${$contract['variable']}->store(\$data);
            return response()->json([
                'message' => 'Created successfully',
                'data' => \$result
            ]);
        } catch (\Exception \$e) {
            return response()->json([
                'error' => \$e->getMessage()
            ], 422);
        }
    }

PHP;
    }

    protected function buildUpdateMethod(string $method, array $contract): string
    {
        $data = $this->getModuleData();
        $actionName = $this->extractActionName();
        return <<<PHP

    /**
     * Update an existing {$this->dto} resource by ID.
     *
     * Payload is accessed via `request()->all()`.
     *
     * @generated
     * @module-action {$actionName}
     * @module-key {$data['module_key']}
     * @param int|string \$id
     * @return \Illuminate\Http\JsonResponse
     */
    public function {$method}(
        int|string \$id,
        {$contract['class']} \${$contract['variable']}
    )
    {
        try {
            \$data = request()->all();
            \$result = \${$contract['variable']}->update(\$id, \$data);
            return response()->json([
                'message' => 'Updated successfully',
                'data' => \$result
            ]);
        } catch (\Exception \$e) {
            return response()->json([
                'error' => \$e->getMessage()
            ], 422);
        }
    }

PHP;
    }

    protected function buildDeleteMethod(string $method, array $contract): string
    {
        $data = $this->getModuleData();
        $actionName = $this->extractActionName();
        return <<<PHP

    /**
     * Delete a {$this->dto} resource by ID.
     *
     * @generated
     * @module-action {$actionName}
     * @module-key {$data['module_key']}
     * @param int|string \$id
     * @return \Illuminate\Http\JsonResponse
     */
    public function {$method}(
        int|string \$id,
        {$contract['class']} \${$contract['variable']}
    )
    {
        try {
            \${$contract['variable']}->delete(\$id);
            return response()->json([
                'message' => 'Deleted successfully'
            ]);
        } catch (\Exception \$e) {
            return response()->json([
                'error' => \$e->getMessage()
            ], 422);
        }
    }

PHP;
    }

    /* =========================================================
     * USE IMPORT & INJECTION
     * ========================================================= */

    protected function addUseStatement(string $fqcn): void
    {
        $content = File::get($this->actionsFile);
        if (str_contains($content, $fqcn)) {
            return;
        }
        $pos = strpos($content, ';');
        if ($pos === false) {
            throw new \RuntimeException('Invalid PHP file: no semicolon after namespace?');
        }
        $content = substr($content, 0, $pos + 1)
                 . "\n\nuse {$fqcn};"
                 . substr($content, $pos + 1);
        File::put($this->actionsFile, $content);
    }

    protected function injectMethod(string $stub): void
    {
        $content = File::get($this->actionsFile);
        $pos = strrpos($content, '}');
        if ($pos === false) {
            throw new \RuntimeException('Invalid Actions class: no closing brace.');
        }
        File::put(
            $this->actionsFile,
            substr($content, 0, $pos) . $stub . "\n}"
        );
    }
}