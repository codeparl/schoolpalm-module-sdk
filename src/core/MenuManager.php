<?php

namespace SchoolPalm\ModuleSDK\Core;

use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Facades\CreatedRegistry;
use SchoolPalm\ModuleBridge\Support\Helper;

class MenuManager
{
    protected array $menus = [];

    public function __construct()
    {
        $this->loadMenus();
    }

    /* ====================================================
     * LOAD MENUS
     * ==================================================== */

    protected function loadMenus(): void
    {
        $modules = collect(CreatedRegistry::filterByStatus('run'));

        $modules->each(function ($module) {

            $menu = $this->addMenu($module['manifest']);

            if (empty($menu)) {
                return;
            }

            // Inject Dashboard automatically
            $this->injectDashboard($menu, $module);

            // Normalize recursively
            $this->normalizeMenu($menu);

            $this->menus[] = $menu;
        });
    }

    protected function addMenu(string $manifestPath): array
    {
        $manifest = Helper::loadJson($manifestPath);

        return $manifest['menus'][0] ?? [];
    }

    /* ====================================================
     * DASHBOARD INJECTION
     * ==================================================== */

    protected function injectDashboard(array &$menu, array $module): void
    {
        if (!isset($menu['children']) || !is_array($menu['children'])) {
            $menu['children'] = [];
        }

        
        $moduleName = Str::after(strtolower($module['module']), '.'); 
        $dashboardRoute = '/' . $moduleName;

        // Check if dashboard already exists
        foreach ($menu['children'] as $child) {
            if (
                isset($child['route']) &&
                $child['route'] === $dashboardRoute
            ) {
                return; 
            }
        }

        // Prepend dashboard as first child
        array_unshift($menu['children'], [
            'name' => $module['module_key'] . '.dashboard',
            'label' => 'Dashboard',
            'icon' => 'lucide-LayoutDashboard',
            'permission' => 'view.' . $moduleName . '.dashboard',
            'route' =>'/'.$module['context']. $dashboardRoute,
            'description' => 'Module dashboard',
            'children' => []
        ]);
    }

    /* ====================================================
     * NORMALIZATION
     * ==================================================== */

    protected function normalizeMenu(array &$menu): void
    {
        if (!is_array($menu)) {
            return;
        }

        // Assign url if route exists
        if (isset($menu['route'])) {
            $menu['url'] = $menu['route'];
        }

        // Remove empty children safely
        if (isset($menu['children'])) {

            if (empty($menu['children'])) {
                unset($menu['children']);
                return;
            }

            foreach ($menu['children'] as &$child) {
                $this->normalizeMenu($child);
            }
        }
    }

    /* ====================================================
     * PUBLIC ACCESS
     * ==================================================== */

    public function getMenus(): array
    {
        return $this->menus;
    }
}