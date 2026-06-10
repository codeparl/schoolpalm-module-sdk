<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Framework Identity
    |--------------------------------------------------------------------------
    | Static platform metadata (non-module specific)
    */
    'framework' => [
        'name'        => 'SchoolPalm',
        'slug'        => 'schoolpalm',
        'website'     => 'https://schoolpalm.app',
        'docs'        => 'https://docs.schoolpalm.app',
        'support'     => 'support@schoolpalm.app',
        'license'     => 'proprietary',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules Root Path
    |--------------------------------------------------------------------------
    */
    'modules_path' => base_path('modules'),
    'install_path' => [
        'backend'=>app_path('packages'),
        'frontend'=>resource_path('js/Pages/Packages')
    ],

    /*
    |--------------------------------------------------------------------------
    | Base Namespace
    |--------------------------------------------------------------------------
    */
    'namespace' => '',

    /*
    |--------------------------------------------------------------------------
    | Default Vendor
    |--------------------------------------------------------------------------
    */
    'vendor' => 'unnovatebrains',

    /*
    |--------------------------------------------------------------------------
    | Default Author
    |--------------------------------------------------------------------------
    */
    'author' => [
        'name'    => env('SCHOOLPALM_AUTHOR_NAME', 'SchoolPalm'),
        'email'   => env('SCHOOLPALM_AUTHOR_EMAIL', null),
        'website' => env('SCHOOLPALM_AUTHOR_WEBSITE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Static Defaults for Generated Manifests
    |--------------------------------------------------------------------------
    | Used by the scaffolder when fields are missing.
    */
    'defaults' => [
        'version'      => '1.0.0',
        'type'         => 'external',
        'role'         => 'admin',
        'is_common'    => true,
        'is_protected' => false,
        'icon'         => 'lucid-layers',
        'levels'=>[]
    ],

    /*
    |--------------------------------------------------------------------------
    | Dependency Defaults
    |--------------------------------------------------------------------------
    | Auto-injected when generating a module.
    */
    'dependencies' => [
        'backend' => [
            'php' => '^8.2',
            'extensions'=>[],
            'packages'=>[]

        ],
        'frontend' => []
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Defaults
    |--------------------------------------------------------------------------
    */
    'menu' => [
        'icon'        => 'lucid-layers',
        'route'       => null,
        'permission'  => 'manage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Defaults
    |--------------------------------------------------------------------------
    */
    'resources' => [
        'ui' => [
            'framework'   => 'vue',
            'tailwind'    => true,
            'source_path' => 'resources/js',
        ],
        'assets' => 'resources/assets',
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Defaults
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'path'           => 'database/migrations',
        'run_on_install' => true,
        'run_on_update'  => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Models Defaults
    |--------------------------------------------------------------------------
    */
    'models' => [
        'path'      => 'Models',
        'autoload'  => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'ttl' => 3600,
        'key' => 'schoolpalm.modules',
    ],

    /*
    |--------------------------------------------------------------------------
    | Developer Experience Flags
    |--------------------------------------------------------------------------
    */
    'dev' => [
        'strict_validation' => true,
        'auto_discover'     => true,
        'auto_register'     => true,
        'fail_fast'         => true,
    ]


];
