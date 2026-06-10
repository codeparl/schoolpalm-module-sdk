<?php

namespace SchoolPalm\ModuleSDK\Support;

use SchoolPalm\ModuleBridge\Facades\CreatedRegistry;

class ModuleAssets
{
    /**
     * Render ONLY JS (used if you want scripts at the bottom)
     */
    public static function renderScript(string $module, int $port = 5175): string
    {
        // =====================
        // DEV MODE (Vite)
        // =====================
        if (app()->environment('local')) {
            return implode("\n", [
                '<script type="module" src="http://localhost:' . $port . '/@vite/client"></script>',
                '<script type="module" src="http://localhost:' . $port . '/index.ts"></script>',
            ]);
        }

        // =====================
        // PROD MODE
        // =====================
        $resolvedData  =  self::resolve($module);
    
        $manifestPath = $resolvedData['manifest'];
        $path = $resolvedData['path'];
        if (!file_exists($manifestPath)) {
            return "<!-- Manifest not found for module {$module} -->";
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        $entry = collect($manifest)->first(
            fn ($e) => !empty($e['isEntry'])
        );

        if (!$entry) {
            return "<!-- No JS entry found for module {$module} -->";
        }

        return '<script type="module" src="/build/modules/' . $path. '/' . $entry['file'] . '"></script>';
    }

    /**
     * Render CSS + JS (used in <head>)
     */
    public static function renderAssets(string $module, int $port = 5175): string
    {
        // =====================
        // DEV MODE (Vite)
        // =====================
        if (app()->environment('local')) {
            return implode("\n", [
                '<script type="module" src="http://localhost:' . $port . '/@vite/client"></script>',
                '<script type="module" src="http://localhost:' . $port . '/index.ts"></script>',
            ]);
        }

        // =====================
        // PROD MODE
        // =====================
        $resolvedData  =  self::resolve($module);
        $html = [];
        $manifestPath = $resolvedData['manifest'];
        $path = $resolvedData['path'];

        if (!file_exists($manifestPath)) {
            return "<!-- Manifest not found for module {$module} -->";
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        $entry = collect($manifest)->first(
            fn ($e) => !empty($e['isEntry'])
        );

        if (!$entry) {
            return "<!-- No entry found for module {$module} -->";
        }

        // ✅ CSS FIRST
        if (!empty($entry['css'])) {
            foreach ($entry['css'] as $css) {
                $html[] = '<link rel="stylesheet" href="/build/' . $path . '/' . $css . '">';
            }
        }


        // ✅ JS
        $html[] = '<script deferred type="module" src="/build/' . $path . '/' . $entry['file'] . '"></script>';
        
        return implode("\n", $html);
      
    }


    private static function resolve($module_key){
       $module =  CreatedRegistry::get($module_key);
       $path  = config('sdk.modules.public_dir').'/'.$module['root'];
      return [
       'manifest'=> public_path("build/{$path}/manifest.json"),
       'path'=>$path
      ];


    }
}
