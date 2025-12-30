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

    /*
    |--------------------------------------------------------------------------
    | Base Namespace
    |--------------------------------------------------------------------------
    */
    'namespace' => 'Modules',

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
            'php' => '^8.2'
        ],
        'frontend' => [],
        'modules' => [],
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
    ],
  'academic_levels'=>  [
    1 => ['label' => 'Early Childhood Education',           'code' => 'ECE'],
    2 => ['label' => 'Primary Education',                   'code' => 'PRI'],
    3 => ['label' => 'Lower Secondary Education',           'code' => 'LSE'],
    4 => ['label' => 'Upper Secondary Education',           'code' => 'USE'],
    5 => ['label' => 'Secondary Education',                 'code' => 'SEC'],
    6 => ['label' => 'Vocational Education',                'code' => 'VOC'],
    7 => ['label' => 'Technical Education',                 'code' => 'TEC'],
    8 => ['label' => 'Tertiary Education',                  'code' => 'TER'],
    9 => ['label' => 'College Education',                   'code' => 'COL'],
    10 => ['label' => 'University Education',               'code' => 'UNI'],
    11 => ['label' => 'Undergraduate Education',            'code' => 'UG'],
    12 => ['label' => 'Postgraduate Education',             'code' => 'PG'],
    13 => ['label' => 'Professional Education',             'code' => 'PRO'],
    14 => ['label' => 'Medical & Health Sciences Education','code' => 'MED'],
    15 => ['label' => 'Teacher Education',                  'code' => 'TED'],
    16 => ['label' => 'Adult & Continuing Education',       'code' => 'ACE'],
    17 => ['label' => 'Special Needs Education',            'code' => 'SNE'],
    18 => ['label' => 'Research-Based Education',           'code' => 'RES'],
]

];
