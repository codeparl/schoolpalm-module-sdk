<?php

namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Facades\CreatedRegistry;
use SchoolPalm\ModuleBridge\Support\Helper as SupportHelper;
use SchoolPalm\ModuleSDK\Console\ModuleCommandBase;
use SchoolPalm\ModuleSDK\Helpers\Helper;

class  FrontendStub extends Stub
{

    private string $frontendPath;
    public function __construct(private string $module_key, ?ModuleCommandBase $cmd = null)
    {

        parent::__construct($module_key, $cmd);
        $this->frontendPath = $this->rootPath . '/Frontend/';
        $this->stubMap =  $this->stubMap['frontend'];
        $this->stubPath =  $this->stubPath . '/frontend';
    }

    public function devPage(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/' . $this->stubMap['devPage'];
        $targetPath  = $this->frontendPath . $this->stubMap['devPage'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }


    public function entry(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/' . $this->stubMap['entry'];
        $targetPath  = $this->frontendPath . $this->stubMap['entry'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function package(): FrontendStub
{
    $stubPath  = $this->stubPath . '/' . $this->stubMap['package'];
    $targetPath  = $this->frontendPath . $this->stubMap['package'];
    $this->publishStub($stubPath, $targetPath);

    return $this;
}

    public function postcss(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/' . $this->stubMap['postcss'];
        $targetPath  = $this->frontendPath . $this->stubMap['postcss'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function tailwind(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/' . $this->stubMap['tailwind'];
        $targetPath  = $this->frontendPath . $this->stubMap['tailwind'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function tsconfig(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/' . $this->stubMap['tsconfig'];
        $targetPath  = $this->frontendPath . $this->stubMap['tsconfig'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function vite_env(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/' . $this->stubMap['vite_env'];
        $targetPath  = $this->frontendPath . $this->stubMap['vite_env'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function vite_config(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/' . $this->stubMap['vite_config'];
        $targetPath  = $this->frontendPath . $this->stubMap['vite_config'];
        $moduleData = ['moduleInfo' => $this->outputStr($this->only(['path', 'module_key', 'root', 'name']))];
       
      
        $this->publishStub($stubPath, $targetPath, $moduleData);
        return $this;
    }

    public function vue_shim(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/' . $this->stubMap['vue_shim'];
        $targetPath  = $this->frontendPath . $this->stubMap['vue_shim'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function css(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/src/assets/css/' . $this->stubMap['css'];
        $targetPath  = $this->frontendPath . 'src/assets/css/' . $this->stubMap['css'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function vueApp(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/src/Pages/' . $this->stubMap['vueApp'];
        $targetPath  = $this->frontendPath . 'src/Pages/' . $this->stubMap['vueApp'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function dashboard(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/src/Pages/' . $this->stubMap['dashboard'];
        $targetPath  = $this->frontendPath . 'src/Pages/' . $this->stubMap['dashboard'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function routes(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/src/router/' . $this->stubMap['routes'];
        $targetPath  = $this->frontendPath . 'src/router/' . $this->stubMap['routes'];

        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function appRuntime(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/src/stores/' . $this->stubMap['appRuntime'];
        $targetPath  = $this->frontendPath . 'src/stores/' . $this->stubMap['appRuntime'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function bootstrap(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/src/' . $this->stubMap['bootstrap'];
        $targetPath  = $this->frontendPath . 'src/' . $this->stubMap['bootstrap'];
      
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function quasarSetup(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/src/' . $this->stubMap['quasarSetup'];
        $targetPath  = $this->frontendPath . 'src/' . $this->stubMap['quasarSetup'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function helper(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/src/' . $this->stubMap['helper'];
        $targetPath  = $this->frontendPath . 'src/' . $this->stubMap['helper'];
        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function scripts(): FrontendStub
    {
        return
            $this->watchScript()
            ->buildScript()
            ->deployScript();
    }

    private function watchScript(): FrontendStub
    {

        $stubPath  = $this->stubPath . '/scripts/' . $this->stubMap['watchScript'];
        $targetPath  = base_path('scripts') . '/' . $this->stubMap['watchScript'];

        $modules =  CreatedRegistry::filterByStatus('run');

        $moduleData = ['modules' => $this->watchList($modules)];
        $this->publishStub($stubPath, $targetPath, $moduleData);
        return $this;
    }

    private function watchList(array $list)
    {
        $content  = "{\n";
        if (empty($list)) return 'null';
        foreach ($list as $key => $module) {
            $path  =  $module['root'];
            $content .= <<<PHP
        '{$module['module_key']}':{\nport: {$module['dev_port']},\npath:'{$path}'},\n
        PHP;
        }

        return $content .= "\n}";
    }

    private function outputStr(array $list)
    {
        if (empty($list)) return 'null';
        return json_encode($list, JSON_PRETTY_PRINT);
    }

    private function deployScript(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/scripts/' . $this->stubMap['deployScript'];
        $targetPath  = base_path('scripts') . '/' . $this->stubMap['deployScript'];
        $modules =  CreatedRegistry::filterByStatus('run');
        $moduleData = ['modules' => $this->watchList($modules)];
        $this->publishStub($stubPath, $targetPath, $moduleData);
        return $this;
    }

    private function buildScript(): FrontendStub
    {
        $stubPath  = $this->stubPath . '/scripts/' . $this->stubMap['buildScript'];
        $targetPath  = base_path('scripts') . '/' . $this->stubMap['buildScript'];

        $modules =  CreatedRegistry::filterByStatus('run');

        $moduleData = ['modules' => $this->watchList($modules)];
        $this->publishStub($stubPath, $targetPath, $moduleData);
        return $this;
    }


    /**
     * update page component stubs when user updates manifest data
     * @param array $menus the menu items from the manifest data
     */
    public function updatePageStubs(array $menus)
    {
            foreach ($menus[0]['children'] as $menu) {
                $this->scanMenuItem($menu);
            }
              return $this;
    }


    protected function createPageFromRoute(string $route)
    {
        $route = trim($route, '/');

        if ($route === '') {
            return;
        }

        //here module becomes action, since we have module/action in $route
        //instead of portal/module/action/id
        $action =  Helper::getRouteSegment('module', $route);
        if (!$action) return;

        $actionClean = preg_replace('/[_\-.]+/', ' ', $action);
        $actionTitle = Str::title($actionClean);

        $componentName = Str::studly($actionTitle);


        $fileName = $componentName . '.vue';

        $dirPath = $this->frontendPath . 'src/Pages/';
        $fullPath = rtrim($dirPath, '/') . '/' . $fileName;
        if (!file_exists($fullPath)) {
            $stubPath = $this->stubPath . '/src/Pages/Page.vue.stub';
            $this->publishStub($stubPath, $fullPath, [
                'pageTitle' => $actionTitle
            ]);

            // ALWAYS ensure router entry exists
            $this->updateRouterStub( $action);
        }
    }

    protected function updateRouterStub( string $action)
    {

        $targetPath  = $this->frontendPath . 'src/router/' . Str::before($this->stubMap['routes'], '.stub');

        if (!file_exists($targetPath)) return;

        $componentName = Str::studly(Str::title($action));

        $content = file_get_contents($targetPath);

        // Prevent duplicates
        if (str_contains($content, "path: '{$action}'")) {
            return;
        }

        $routeBlock = <<<TS
  {
    path: '/{$action}',
    name: '{$action}',
    component: () => import('../Pages/{$componentName}.vue'),
  },

TS;

        // Inject before closing ]
        $content = preg_replace(
            '/const routes: RouteRecordRaw\[\] = \[(.*?)\]/s',
            "const routes: RouteRecordRaw[] = [$1\n{$routeBlock}]",
            $content
        );
        file_put_contents($targetPath, $content);
    }


    protected function scanMenuItem(array $menu)
    {
        if (!empty($menu['route'])) {
            $this->createPageFromRoute($menu['route']);
        }

        if (!empty($menu['children']) && is_array($menu['children'])) {
            foreach ($menu['children'] as $child) {
                $this->scanMenuItem($child);
            }
        }
    }
}
