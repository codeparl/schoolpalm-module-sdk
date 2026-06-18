<?php

namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Services\DtoReflectionService;
use SchoolPalm\ModuleBridge\Manifest\ModuleManifest;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

class FrontendPageGenerator
{
    protected string $stubBasePath;
    protected string $frontendPath;
    protected string $rootPath;
    protected string $routerFile;
    protected ?string $apiRoute;

    public function __construct(
        protected array $action,
        protected string $type,
        protected string $dto,
        protected DtoReflectionService $dtoService,
        protected ModuleManifest $manifest,
        ?string $stubBasePath = null,
        ?string $apiRoute = null
    ) {
        $this->rootPath = ModulePaths::modulePath($this->manifest->root());
        $this->frontendPath = rtrim($this->rootPath, '/') . '/Frontend';
        $this->stubBasePath = $stubBasePath ?? ModulePaths::stubsPath() . '/frontend/actions';
        $this->apiRoute = $apiRoute;

        $this->routerFile = $this->frontendPath . '/src/router/index.ts';
        if (!File::exists($this->routerFile)) {
            $this->routerFile = $this->frontendPath . '/src/router/routes.ts';
        }
    }

    public function generate(): void
    {
        $fields = $this->dtoService->fields($this->dto);
        $pageName = $this->resolvePageName();
        $routePath = $this->extractRoutePath();

        match ($this->type) {
            'create' => $this->generateFromStub('create-page.stub', $pageName, $fields, $routePath),
            'edit'   => $this->generateFromStub('edit-page.stub',   $pageName, $fields, $routePath),
            'view'   => $this->generateFromStub('view-page.stub',   $pageName, $fields, $routePath),
            'list'   => $this->generateFromStub('list-page.stub',   $pageName, $fields, $routePath),
            default  => throw new \RuntimeException("Unsupported frontend type [{$this->type}]")
        };
    }

    protected function resolvePageName(): string
    {
        $route = $this->action['route'] ?? '';
        $cleanRoute = preg_replace('/\/(?:\{id\}|:id)$/', '', $route);
        $segments = array_filter(explode('/', trim($cleanRoute, '/')));
        $lastSegment = end($segments);
        return Str::studly(Str::replace(['-', '_'], ' ', $lastSegment));
    }

    protected function extractRoutePath(): string
    {
        $route = $this->action['route'] ?? '';
        $cleanRoute = preg_replace('/\/(?:\{id\}|:id)$/', '', $route);
        $segments = array_filter(explode('/', trim($cleanRoute, '/')));
        return end($segments);
    }

    protected function resolveHttpMethod(): string
    {
        return $this->action['method'] ?? match ($this->type) {
            'list', 'view' => 'GET',
            'create'       => 'POST',
            'edit'         => 'PUT',
            default        => 'GET'
        };
    }

    protected function getEntitySlugs(): array
    {
        $entityName = str_replace('Data', '', class_basename($this->dto));
        $slug = Str::kebab($entityName);
        return [
            'list_route'   => 'list-' . Str::kebab(Str::plural($entityName)),
            'view_route'   => 'view-' . $slug,
            'edit_route'   => 'edit-' . $slug,
            'update_route'   => 'update-' . $slug,
            'delete_route' => 'delete-' . $slug,
            'store_route' => 'store-' . $slug,
        ];
    }

    protected function generateFromStub(string $stubName, string $pageName, array $fields, string $routePath): void
    {
        $stubPath = $this->stubBasePath . '/' . $stubName;
        if (!File::exists($stubPath)) {
            throw new \RuntimeException("Stub missing: {$stubPath}");
        }

        $content = File::get($stubPath);

        $slugData = $this->getEntitySlugs();

        // Use provided apiRoute or fallback to action['route']
        $apiRoute = $this->apiRoute ?? $this->action['route'];
        $title  = Str::before(Str::afterLast($this->dto,'\\'),'Data');

        $replacements = array_merge([
            'dtoName'     => $title,
            'method'      => $this->resolveHttpMethod(),
            'form_fields' => $this->generateFormFields($fields),
            'rows'        => $this->generateViewRows($fields),
            'columns'     => $this->generateListColumns($fields),
            'route'       => $this->action['route'],
            'api_route'   => $apiRoute,
            'list_route'  => $slugData['list_route'],
            'view_route'  => $slugData['view_route'],
            'edit_route'  => $slugData['edit_route'],
            'delete_route'=> $slugData['delete_route'],
            'store_route'  => $slugData['store_route'],
            'update_route'   =>  $slugData['update_route'],
        ], $this->getModuleData());

        $content = preg_replace_callback('/{{\s*(\w+)\s*}}/', function ($matches) use ($replacements) {
            return $replacements[$matches[1]] ?? $matches[0];
        }, $content);

        $this->writeFile($pageName, $content);
        $this->updateRouter($routePath, $pageName);
    }

    protected function updateRouter(string $routePath, string $componentName): void
    {
        if (!File::exists($this->routerFile)) {
            return;
        }

        $content = File::get($this->routerFile);

        if (preg_match(
            "/path\s*:\s*['\"]\/" . preg_quote($routePath, '/') . "['\"]/",
            $content
        )) {
            return;
        }

        $fullPath = match ($this->type) {
            'view', 'edit' => "{$routePath}/:id",
            default        => $routePath,
        };

        $routeBlock = <<<TS

  {
    path: '/{$fullPath}',
    name: '{$routePath}',
    component: () => import('../Pages/{$componentName}.vue'),
  },
TS;

        $arrayEnd = strrpos($content, ']');
        if ($arrayEnd === false) {
            throw new \RuntimeException('Could not locate routes array.');
        }

        $newContent = substr($content, 0, $arrayEnd) . $routeBlock . "\n" . substr($content, $arrayEnd);
        File::put($this->routerFile, $newContent);
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
            'moduleId'     => str_replace('.', '_', $key) . '_app',
            'namespace'    => $this->manifest->info()->namespace(),
            'package_name' => '@' . $vendor . '/' . $moduleName,
        ];
    }

    protected function generateFormFields(array $fields): string
    {
        return collect($fields)->map(function ($f) {
            return match ($f['type']) {
                'int', 'float' => "\n<q-input v-model=\"form.{$f['name']}\" type=\"number\" label=\"{$f['name']}\" />",
                'bool'         => "\n<q-toggle v-model=\"form.{$f['name']}\" label=\"{$f['name']}\" />",
                default        => "\n<q-input v-model=\"form.{$f['name']}\" label=\"{$f['name']}\" />",
            };
        })->implode('');
    }

    protected function generateViewRows(array $fields): string
    {
        return collect($fields)->map(fn ($f) => "
            <q-item>
              <q-item-section>
                <q-item-label>{$f['name']}</q-item-label>
                <q-item-label caption>{{ item?.{$f['name']} }}</q-item-label>
              </q-item-section>
            </q-item>
        ")->implode("\n");
    }

    protected function generateListColumns(array $fields): string
    {
        return collect($fields)->map(fn ($f) => "
          { name: '{$f['name']}', label: '{$f['name']}', field: '{$f['name']}' }
        ")->implode(',');
    }

    protected function writeFile(string $pageName, string $content): void
    {
        $path = rtrim($this->frontendPath, '/') . "/src/Pages/{$pageName}.vue";
        $dir = dirname($path);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }
        File::put($path, $content);
    }
}