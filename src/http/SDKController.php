<?php

namespace SchoolPalm\ModuleSDK\Http;

use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Inertia\Inertia;
use SchoolPalm\ModuleBridge\Facades\CreatedRegistry;
use SchoolPalm\ModuleSDK\Support\ModulePorts;
use SchoolPalm\ModuleBridge\Support\Helper;
use SchoolPalm\ModuleSDK\core\ModuleResolver;

class SDKController extends Controller
{
    public function handle($module, $action = null)
    {
        $moduleKey = Str::lower($module);

        $realModule = CreatedRegistry::get($moduleKey);

    
        if (!$realModule) {
            abort(404, 'Module not registered');
        }

        $jsPath = public_path(
            'build/modules/' .
            trim($realModule['root'], '/\\') .
            "/{$realModule['module']}.js"
        );
 


        if (!file_exists($jsPath)) {
            abort(404, "Module not built");
        }



        return view('shell', [
            'module' => $realModule['module'],
            'port' => $realModule['dev_port'],
            'app_id' => $realModule['app_id']
        ]);
    }

    public function moduleHost($context,$module, $action = null)
    {
        $isDev = app()->environment('local');

        $portInfo = ModulePorts::get(Str::lower($module));

        if (!$portInfo) {
            abort(404, 'Module port not found');
        }

        $context  =  Helper::modulePart($portInfo['key'],'context');

        return Inertia::render('ModuleHost', [
            'module' => [
                'url' => $isDev
                    ? "http://localhost:{$portInfo['port']}/index.html"
                    : "/modules/{$context}/{$module}/{$action}",

                'id' => $portInfo['key'],
                'port' => $portInfo['port'],
                'name' => $portInfo['key'],
                'route' => "/modules/{$context}/{$module}/{$action}",
            ],

            'context' => $context,
            'isSDK' => false
        ]);
    }

    public function moduleApi()
    {
        $moduleKey = Helper::getPathSegment('module');

        if (!CreatedRegistry::exists($moduleKey)) {
            abort(404, 'Module not found');
        }

        $resolver = new ModuleResolver($moduleKey);

        $class = $resolver->resolveModuleMainClass();


        if (!$class || !class_exists($class)) {
            abort(404, 'Module entry class not found');
        }


        try {
            $entry = app($class);
            $entry->init($resolver->module);
            return $entry->performAction();
        } catch (\Throwable $e) {
            report($e);
            abort(500,$e->getMessage());
        }
    }

    public function sdkHandler()
    {

    $entry  =  new \App\SDK\ActionEntry;
     return $entry->performAction();

    }
}
