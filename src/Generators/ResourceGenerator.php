<?php

namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Manifest\ManifestFactory;
use SchoolPalm\ModuleBridge\Manifest\ModuleManifest;
use SchoolPalm\ModuleBridge\Services\DtoReflectionService;

class ResourceGenerator
{
    protected DtoReflectionService $dtoReflection;
    protected string $entityName;
    protected string $entitySlugSingular;
    protected string $entitySlugPlural;
    protected string $basePath;
    protected string $manifestPath;
    protected string $vendor;
    protected string $context;
    protected string $module;
    protected string $moduleKey;
    protected string $fullModule;

    public function __construct(
        protected string $actionsFile,
        protected string $dto,
        protected ModuleManifest $manifest,
    ) {
        $this->dtoReflection = new DtoReflectionService($manifest);

        $dtoName = class_basename($dto);
        $this->entityName = str_replace('Data', '', $dtoName);
        $this->entitySlugSingular = Str::kebab($this->entityName);
        $this->entitySlugPlural = Str::kebab(Str::plural($this->entityName));

        $this->vendor = $this->manifest->vendor();
        $this->context = $this->manifest->info()->context();
        $this->module = $this->extractModuleName();
        $this->moduleKey = $this->manifest->key();
        $this->fullModule = Str::after($this->moduleKey, $this->vendor . '.');

        $this->basePath = "/{$this->context}/{$this->module}";
        $this->manifestPath = $this->manifest->path() . '/manifest.json';
    }

    protected function extractModuleName(): string
    {
        $root = $this->manifest->root();
        $parts = explode('/', $root);
        return Str::kebab(end($parts));
    }

    protected function buildPermission(string $route): string
    {
        $clean = ltrim($route, '/');
        $permission = str_replace(['-', '_', '/'], '.', $clean);
        return "{$this->vendor}.{$this->fullModule}.{$permission}";
    }

    protected function buildMenuName(string $permission, bool $isParent = false): string
    {
        if ($isParent) {
            return "{$this->vendor}.{$this->fullModule}.manage.{$this->entitySlugSingular}";
        }
        $modulePart = Str::after($this->moduleKey, $this->vendor . '.');
        return "{$this->vendor}.{$modulePart}.{$permission}";
    }

    public function generate(): void
    {
        $operations = [
            'list' => [
                'type'         => 'list',
                'backendRoute' => $this->basePath . '/list-' . $this->entitySlugPlural,
                'uiRoute'      => $this->basePath . '/list-' . $this->entitySlugPlural,
                'method'       => 'GET',
                'ui'           => true,
                'menu'         => true,
                'label'        => 'List ' . Str::plural($this->entityName),
                'icon'         => 'lucide-LayoutList',
            ],
            'store' => [
                'type'         => 'store',
                'backendRoute' => $this->basePath . '/store-' . $this->entitySlugSingular,
                'uiRoute'      => $this->basePath . '/create-' . $this->entitySlugSingular,
                'method'       => 'POST',
                'ui'           => true,
                'menu'         => true,
                'label'        => 'Add ' . $this->entityName,
                'icon'         => 'lucide-UserPlus',
                'uiType'       => 'create',
            ],
            'view' => [
                'type'         => 'view',
                'backendRoute' => $this->basePath . '/view-' . $this->entitySlugSingular,
                'uiRoute'      => $this->basePath . '/view-' . $this->entitySlugSingular . '/:id',
                'method'       => 'GET',
                'ui'           => true,
                'menu'         => false,
                'label'        => 'View ' . $this->entityName,
            ],
            'update' => [
                'type'         => 'update',
                'backendRoute' => $this->basePath . '/update-' . $this->entitySlugSingular,
                'uiRoute'      => $this->basePath . '/edit-' . $this->entitySlugSingular . '/:id',
                'method'       => 'PUT',
                'ui'           => true,
                'menu'         => false,
                'label'        => 'Edit ' . $this->entityName,
                'uiType'       => 'edit',
            ],
            'delete' => [
                'type'         => 'delete',
                'backendRoute' => $this->basePath . '/delete-' . $this->entitySlugSingular,
                'uiRoute'      => null,
                'method'       => 'DELETE',
                'ui'           => false,
                'menu'         => false,
                'label'        => 'Delete ' . $this->entityName,
            ],
        ];

        $generatedActions = [];
        $menuChildren = [];

        foreach ($operations as $op) {
            $permission = $this->buildPermission($op['backendRoute']);

            // 1. Generate backend method (uses backendRoute)
            $backendAction = [
                'route'      => $op['backendRoute'],
                'method'     => $op['method'],
                'source'     => 'backend',
                'permission' => $permission,
                'description' => $op['label'],
            ];

            $backendGen = new BackendActionGenerator(
                $this->actionsFile,
                $backendAction,
                $op['type'],
                $this->dto,
                $this->dtoReflection,
                $this->manifest
            );
            $backendGen->generate();

            // 2. Generate UI page if needed (uses uiRoute)
            if ($op['ui'] && $op['uiRoute']) {
                $uiType = $op['uiType'] ?? $op['type'];
                $frontendAction = [
                    'route'      => $op['uiRoute'],
                    'method'     => $op['method'],
                    'source'     => 'menu',
                    'permission' => $permission,
                    'description' => $op['label'],
                ];
                $frontendGen = new FrontendPageGenerator(
                    $frontendAction,
                    $uiType,
                    $this->dto,
                    $this->dtoReflection,
                    $this->manifest,
                    null,
                    $op['backendRoute'] // pass backendRoute for axios calls
                );
                $frontendGen->generate();
            }

            // 3. Add action to manifest (uses backendRoute, without {id})
            $manifestRoute = $op['backendRoute'];
            $generatedActions[] = [
                'route'      => $manifestRoute,
                'method'     => $op['method'],
                'permission' => $permission,
                'description' => $op['label'],
            ];

            // 4. Add menu children for list and store (uses uiRoute)
            if ($op['menu']) {
                $menuChildren[] = [
                    'name' => $this->buildMenuName($permission, false),
                    'label' => $op['label'],
                    'icon' => $op['icon'] ?? 'lucide-Layers',
                    'permission' => $permission,
                    'route' => $op['uiRoute'],
                    'description' => $op['label'],
                    'children' => [],
                ];
            }
        }

        // Build parent menu
        $parentPermission = "manage.{$this->entitySlugSingular}";
        $parentMenu = [
            'name' => $this->buildMenuName($parentPermission, true),
            'label' => ucfirst($this->entityName),
            'icon' => $this->manifest->info()->icon ?? 'lucide-Boxes',
            'permission' => $parentPermission,
            'route' => null,
            'description' => $this->manifest->info()->description ?? '',
            'children' => $menuChildren,
        ];

        $this->updateManifest($generatedActions, [$parentMenu]);
    }

    protected function updateManifest(array $newActions, array $newMenus): void
    {
        $existing = ManifestFactory::loadManifest($this->manifestPath);
        if (!$existing) {
            throw new \RuntimeException("Manifest not found: {$this->manifestPath}");
        }

        $mergedActions = $this->mergeActions($existing['actions'] ?? [], $newActions);
        $mergedMenus = $this->mergeMenus($existing['menus'] ?? [], $newMenus);

        ManifestFactory::update($existing, [
            'actions' => $mergedActions,
            'menus'   => $mergedMenus,
        ], $this->manifestPath);
    }

    protected function mergeActions(array $existing, array $new): array
    {
        $map = [];
        foreach ($existing as $a) {
            $key = $a['route'] . '|' . $a['method'];
            $map[$key] = $a;
        }
        foreach ($new as $a) {
            $key = $a['route'] . '|' . $a['method'];
            if (!isset($map[$key])) {
                $map[$key] = $a;
            }
        }
        return array_values($map);
    }

    protected function mergeMenus(array $existing, array $new): array
    {
        foreach ($new as $newParent) {
            $found = false;
            foreach ($existing as &$existingParent) {
                if ($existingParent['name'] === $newParent['name']) {
                    $found = true;
                    $existingRoutes = [];
                    foreach ($existingParent['children'] as $child) {
                        if ($child['route']) {
                            $existingRoutes[] = $child['route'];
                        }
                    }
                    foreach ($newParent['children'] as $newChild) {
                        if (!in_array($newChild['route'], $existingRoutes)) {
                            $existingParent['children'][] = $newChild;
                        }
                    }
                    break;
                }
            }
            if (!$found) {
                $existing[] = $newParent;
            }
        }
        return $existing;
    }
}