<?php

namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Database\SchemaExporter;
use SchoolPalm\ModuleBridge\Facades\CreatedRegistry;
use SchoolPalm\ModuleBridge\Factories\SchemaDataFactoryInjector;
use SchoolPalm\ModuleBridge\Support\Helper;
use SchoolPalm\ModuleSDK\Console\ModuleCommandBase;
use SchoolPalm\ModuleBridge\Manifest\ManifestFactory;
use  SchoolPalm\ModuleBridge\Generators\ModuleContract;
use SchoolPalm\ModuleBridge\Relations\RelationSpec;
use SchoolPalm\ModuleBridge\Generators\RelationProviderGenerator as RelationGen;
use SchoolPalm\ModuleBridge\Profiles\ContractProfile;
use SchoolPalm\ModuleBridge\Services\DtoReflectionService;

class BackendStub extends Stub
{
    private string $backendPath;

    public function __construct(private string $module_key, ?ModuleCommandBase $cmd = null)
    {
        parent::__construct($module_key, $cmd);

        $this->backendPath = $this->rootPath . '/Backend/';
        $this->stubMap = $this->stubMap['backend'];
        $this->stubPath = $this->stubPath . '/backend';
    }

    public function main(): BackendStub
    {
        $stubPath   = $this->stubPath . '/' . $this->stubMap['main'];
        $targetPath = $this->backendPath . '/' . $this->manifest->moduleEntry();

        $this->publishStub($stubPath, $targetPath);
        return $this;
    }


    public function module(): BackendStub
    {
        $stubPath   = $this->stubPath . '/' . $this->stubMap['module'];
        $targetPath = $this->backendPath  . $this->stubMap['module'];

        $this->publishStub($stubPath, $targetPath);
        return $this;
    }
    protected function resolveModelClass(string $tableName): string
    {
        return Str::studly(Str::singular($tableName));
    }

    protected function resolveModelNamespace(): string
    {
        return $this->manifest->models->namespace;
    }
    public function contracts(): BackendStub
    {
        $contracts = $this->manifest->providedContracts;

        foreach ($contracts as $contract) {

            $namespace = Helper::beforeLast($contract, '\\');
            $name = Helper::afterLast($contract, '\\');

            $dtoFqcn = $this->resolveDtoFromContract($contract);
            $dtoShort = class_basename($dtoFqcn);

            $stubPath = $this->stubPath . '/' . $this->stubMap['contracts'];
            $targetPath = $this->backendPath . 'Contracts/' . $name . '.php';

            if (File::exists($targetPath)) {
                continue;
            }

            $content = [
                'namespace' => $namespace,
                'class' => $name,
                'dto' => $dtoShort,
                'dtoFqcn' => $dtoFqcn,
            ];

            $this->publishStub($stubPath, $targetPath, $content);

            $mContract = app(ModuleContract::class);
            $mContract->executeContract($contract, profile: ContractProfile::API());

            $this->coreContract(str_replace('Contract', '', $name));
        }
        return $this;
    }

    public function queryEngine(
        $name,
        string $modelClass = null,
        string $modelNamespace = null
    ): BackendStub {

        $namespace = $this->module['namespace'] . '\Services\Core';

        $name .= 'QueryEngine';

        $stubPath = $this->stubPath . '/' . $this->stubMap['query-engine'];
        $targetPath = $this->backendPath . 'Services/Core/' . $name . '.php';

        if (File::exists($targetPath)) {
            return $this;
        }

        $content = [
            'namespace' => $namespace,
            'class' => $name,
            'modelClass' => $modelClass,
            'modelNamespace' => $modelNamespace,
        ];

        $this->publishStub($stubPath, $targetPath, $content);

        return $this;
    }

    public function baseService(
        string $modelClass = null,
        string $modelNamespace = null
    ): BackendStub {

        $namespace = $this->module['namespace'] . '\Services\Core';

        $stubPath = $this->stubPath . '/' . $this->stubMap['base-service'];
        $targetPath = $this->backendPath . 'Services/Core/BaseService.php';

        if (File::exists($targetPath)) {
            return $this;
        }

        $name = Helper::modulePart($this->module['namespace']);
        $name .= 'QueryEngine';

        $engineNamespace = $this->module['namespace'] . '\Services\Core\\' . $name;

        $content = [
            'engineNamespace' => $engineNamespace,
            'engineClass' => $name,
            'namespace' => $namespace,
            'moduleKey' => $this->module_key,

            // ✅ ADDED
            'modelClass' => $modelClass,
            'modelNamespace' => $modelNamespace,
        ];

        $this->publishStub($stubPath, $targetPath, $content);

        return $this;
    }


    public function coreContract($name): BackendStub
    {
        $namespace = $this->module['namespace'] . '\\Contracts\Core';

        $contractName = $name . 'CoreContract';

        $stubPath = $this->stubPath . '/' . $this->stubMap['core-contracts'];
        $targetPath = $this->backendPath . 'Contracts/Core/' . $contractName . '.php';

        if (File::exists($targetPath)) {
            return $this;
        }

        $content = [
            'namespace' => $namespace,
            'class' => $contractName,
        ];

        $this->publishStub($stubPath, $targetPath, $content);

        $contract = $namespace . '\\' . $contractName;

        $servicePath = $this->backendPath . 'Services/Core/' . $name . 'CoreService.php';

        $mContract = app(ModuleContract::class);

        $mContract->executeContract(
            $contract,
            profile: ContractProfile::INTERNAL(),
            customPath: $servicePath
        );

        /*
        |--------------------------------------------------------------------------
        | MODEL CONTEXT AUTO-INJECTION
        |--------------------------------------------------------------------------
        */

        $modelClass = $this->resolveModelClass($name);
        $modelNamespace = $this->resolveModelNamespace();


        return $this
            ->queryEngine($name, $modelClass, $modelNamespace)
            ->baseService($modelClass, $modelNamespace);
    }

    public function contractReadme(): BackendStub
    {
        $stubPath   = $this->stubPath . '/' . $this->stubMap['contract-readme'];
        $targetPath = $this->backendPath . 'Contracts/README.md';
        $data = [];
        $this->publishStub($stubPath, $targetPath, $data);
        return $this;
    }

    public function generateDtosFromSchemas(): self
    {
        $contracts = $this->manifest->providedContracts ?? [];

        if (empty($contracts)) {
            return $this;
        }

        $schemaDir = $this->backendPath . '/Database/migrations/schemas';
        $dtoDir    = $this->backendPath . '/DTOs';

        if (!is_dir($schemaDir)) {
            File::makeDirectory($schemaDir);
        }

        if (!is_dir($dtoDir)) {
              File::makeDirectory($dtoDir );
        }

        $namespace = rtrim(
            $this->manifest->info()->namespace(),
            '\\'
        ) . '\\DTOs';

        $injector = new SchemaDtoInjector();

        /*
    |--------------------------------------------------------------------------
    | Load files once
    |--------------------------------------------------------------------------
    */
        $schemaFiles = glob($schemaDir . '/*.json');
        $dtoFiles    = glob($dtoDir . '/*.php');

        foreach ($contracts as $contract) {

            /*
        |--------------------------------------------------------------------------
        | Contract → Base Name
        | IdManagerContract → IdManager
        |--------------------------------------------------------------------------
        */
            $contractName = class_basename($contract);
            $coreName     = str_replace('Contract', '', $contractName);

            /*
        |--------------------------------------------------------------------------
        | Normalize for matching
        |--------------------------------------------------------------------------
        */
            $normalizedCore = strtolower(
                preg_replace('/(?<!^)[A-Z]/', '_$0', $coreName)
            );

            /*
        |--------------------------------------------------------------------------
        | 1. Resolve Schema
        |--------------------------------------------------------------------------
        */
            $schemaPath = null;

            foreach ($schemaFiles as $file) {

                $filename = strtolower(basename($file));

                if (
                    str_contains($filename, $normalizedCore) ||
                    str_contains($filename, strtolower($coreName))
                ) {
                    $schemaPath = $file;
                    break;
                }
            }

            if (!$schemaPath) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | 2. Resolve DTO
        |--------------------------------------------------------------------------
        | Match: IdManager → IdManagerDTO.php
        |--------------------------------------------------------------------------
        */
            $dtoPath = null;

            foreach ($dtoFiles as $file) {

                $filename = strtolower(basename($file));

                if (str_starts_with($filename, strtolower($coreName))) {
                    $dtoPath = $file;
                    break;
                }
            }

            if (!$dtoPath) {
                throw new \RuntimeException(
                    "DTO not found for [{$coreName}]"
                );
            }

            /*
        |--------------------------------------------------------------------------
        | 3. Validate Schema
        |--------------------------------------------------------------------------
        */
            $schema = json_decode(file_get_contents($schemaPath), true);

            if (!$schema || !isset($schema['columns'])) {
                throw new \RuntimeException(
                    "Invalid schema for [{$contractName}]"
                );
            }

            /*
        |--------------------------------------------------------------------------
        | 4. Inject DTO fields
        |--------------------------------------------------------------------------
        */
            $injector->generate(
                $schemaPath,
                $dtoPath,
                $namespace,
                basename($dtoPath, '.php')
            );
        }

        return $this;
    }



    public function publishEvents(): void
    {
        $eventsPath = $this->backendPath . '/Events';

        if (!File::exists($eventsPath)) {
            return;
        }

        $files = File::files($eventsPath);

        $events = [];

        foreach ($files as $file) {

            // Only PHP files
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            $namespace = $this->manifest->info()->namespace() . '\\Events';

            $fqcn = $namespace . '\\' . $className;

            $events[] = $fqcn;
        }

        if (empty($events)) {
            return;
        }

        // Load existing manifest
        $existingManifest = $this->manifest->raw();

        $filePath = $this->module['manifest'];

        // Merge without duplicates
        $existingEvents = $existingManifest['events'] ?? [];

        $merged = array_unique(array_merge($existingEvents, $events));

        $data = [
            'events' => $merged
        ];

        ManifestFactory::update($existingManifest, $data, $filePath);
    }

    protected function resolveDtoFromContract(string $contract): string
    {
        $class = Helper::afterLast($contract, '\\');

        $dtoName = str_replace('Contract', 'Data', $class);

        $baseNamespace = Helper::beforeLast($contract, '\\');

        $dtoNamespace = str_replace('\\Contracts', '\\DTOs', $baseNamespace);

        return $dtoNamespace . '\\' . $dtoName;
    }

    public function dtos(): BackendStub
    {
        $contracts = $this->manifest->providedContracts;

        foreach ($contracts as $contract) {

            // Extract contract name
            $contractName = Helper::afterLast($contract, '\\');

            // Ensure naming convention
            if (!str_ends_with($contractName, 'Contract')) {
                continue;
            }

            // Convert Contract → Data
            $dtoName = str_replace('Contract', 'Data', $contractName);

            // Build namespace
            $baseNamespace = Helper::beforeLast($contract, '\\');
            $dtoNamespace = str_replace('\\Contracts', '\\DTOs', $baseNamespace);

            $stubPath   = $this->stubPath . '/' . $this->stubMap['dtos'];
            $targetPath = $this->backendPath . 'DTOs/' . $dtoName . '.php';

            // Skip if already exists (important)
            if (File::exists($targetPath)) {
                continue;
            }

            $content = [
                'namespace' => $dtoNamespace,
                'class'     => $dtoName,
            ];

            $this->publishStub($stubPath, $targetPath, $content);
        }

        return $this;
    }

    public function actions(): BackendStub
    {


        $className = Str::studly($this->manifest->info()->name()) . 'Actions';
        $stubPath   = $this->stubPath . '/' . $this->stubMap['actions'];
        $targetPath = $this->backendPath . 'Actions/' . $className . '.php';

        // Skip if already exists
        if (File::exists($targetPath)) {
            return $this;
        }

        $data = [
            'namespace' => $this->module['namespace'] . '\Actions',
            'class' => $className
        ];

        $this->publishStub($stubPath, $targetPath, $data);
        return $this;
    }



    public function providers(): BackendStub
    {
        $className = Str::afterLast($this->manifest->entry->provider(), '\\');

        $stubPath = rtrim($this->stubPath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $this->stubMap['providers'];

        $targetPath = rtrim($this->backendPath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'Providers'
            . DIRECTORY_SEPARATOR
            . $className . '.php';

        $data = [
            'namespace' =>  $this->module['namespace'] . '\Providers',
            'module_namespace' => $this->module['namespace'],
            'class' => $className,
        ];

        $this->publishStub($stubPath, $targetPath, $data);

        return $this;
    }

    public  function createRelationClass()
    {
        //create relations class file if not found 
        $manifest  = $this->manifest->raw();
        $className = class_basename($manifest['relations']);
        $file  = $className . '.php';
        $namespace =  $this->manifest->info()->namespace() . '\Relations';

        $path  = $this->backendPath . '/Relations/' . $file;
        $definitions  = [
            'class' => $className,
            'namespace' => $namespace,
            'relations' => []
        ];

        if (!File::exists($path)) 
            (new RelationGen())->generate($definitions, $path);

        return $this;
    }
    public function relations(array|null $relations = []): BackendStub
    {

        if (!$relations) return $this;

        $relations =  RelationSpec::normalizeRelations($relations);
        $path  = $this->backendPath . '/Relations';
        Helper::syncManifestRelations($this->manifest->raw(), $relations, $path);
        return $this;
    }


  public function models(): BackendStub
{
    $stubPath = $this->stubPath . '/' . $this->stubMap['models'];
    $dtoService = new DtoReflectionService($this->manifest);

    $tables = $this->manifest->migrations->tables(true);
    foreach ($tables as $table) {
        $class = Str::studly(Str::singular($table['name']));
        $targetPath = $this->backendPath . 'Models/' . $class . '.php';

        $tableName = function () use ($table) {
            $prefix = str_replace('.', '_', $this->module_key);
            return "{$prefix}_{$table['name']}";
        };

        // Get fillable fields from DTO if possible
        $dtoShortName = $class . 'Data';
        try {
            $fields = $dtoService->fields($dtoShortName);
            $fillable = collect($fields)->pluck('name')->map(fn($name) => "'{$name}'")->implode(",\n        ");
        } catch (\Exception $e) {
            $fillable = '';
        }

        $data = [
            'namespace' => $this->manifest->models->namespace,
            'class'     => $class,
            'table'     => $tableName(),
            'fillable_fields' => $fillable,
        ];

        if (!File::exists($targetPath)) {
            $this->publishStub($stubPath, $targetPath, $data);
        }
    }

    return $this;
}

public function updateModels(): BackendStub
{
    $dtoService = new DtoReflectionService($this->manifest);
    $tables = $this->manifest->migrations->tables(true);

    foreach ($tables as $table) {
        $class = Str::studly(Str::singular($table['name']));
        $modelPath = $this->backendPath . 'Models/' . $class . '.php';

        if (!File::exists($modelPath)) {
            continue;
        }

        $dtoShortName = $class . 'Data';
        try {
            $fields = $dtoService->fields($dtoShortName);
            $fillable = collect($fields)->pluck('name')->map(fn($name) => "'{$name}'")->implode(",\n        ");
        } catch (\Exception $e) {
            // DTO not found – skip or leave empty
            $fillable = '';
        }

        $content = File::get($modelPath);

        // Pattern to match protected $fillable = [...];
        $pattern = '/(protected\s+\$fillable\s*=\s*\[)(.*?)(\];)/s';
        $replacement = "$1\n        {$fillable}\n    $3";

        if (preg_match($pattern, $content)) {
            $newContent = preg_replace($pattern, $replacement, $content);
            File::put($modelPath, $newContent);
        } else {
            // If the fillable array is missing, inject it after the table definition
            $injectPattern = '/(protected\s+\$table\s*=\s*[^;]+;)/';
            $injectReplacement = "$1\n\n    protected \$fillable = [\n        {$fillable}\n    ];";
            if (preg_match($injectPattern, $content)) {
                $newContent = preg_replace($injectPattern, $injectReplacement, $content);
                File::put($modelPath, $newContent);
            }
        }
    }

    return $this;
}

    public function events(): BackendStub
    {
        $stubPath   = $this->stubPath . '/' . $this->stubMap['events'];
        $targetPath = $this->backendPath . 'Events/' . $this->stubMap['events'];

        $this->publishStub($stubPath, $targetPath);
        return $this;
    }



public function migrations(): BackendStub
{
    $stubPath = $this->stubPath . '/' . $this->stubMap['migrations'];
    $tables = $this->manifest->migrations->tables();
    $manifestPath = $this->manifest->path() . '/manifest.json';

    foreach ($tables as $table) {
        $prefix = str_replace('.', '_', $this->module_key);
        $tableName = "{$prefix}_{$table['name']}";
        $class = 'Create' . Str::studly($tableName) . 'Table';

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_create_{$tableName}_table.php";
        $targetDir = $this->backendPath . 'Database/migrations/';
        $targetPath = $targetDir . $fileName;

        $existingFiles = File::files($targetDir);
        $alreadyExists = collect($existingFiles)->contains(function ($file) use ($tableName) {
            return str_contains($file->getFilename(), "create_{$tableName}_table.php");
        });

        if ($alreadyExists || Schema::hasTable($tableName)) {
            continue;
        }

        $columns = $table['columns'] ?? [];

        // Generate schema file name with the same timestamp
        $schemaFileName = str_replace('.php', '.json', $fileName);
        $schemaOutputPath = $this->backendPath . 'Database/migrations/schemas/' . $schemaFileName;

        $result = SchemaExporter::exportFromManifest(
            $table['name'],          // original table name (without prefix)
            $columns,
            $targetPath,
            $schemaOutputPath,
            $manifestPath
        );

        $data = [
            'class'   => $class,
            'table'   => $tableName,
            'columns' => $result['columns_code'],
        ];

        $this->publishStub($stubPath, $targetPath, $data);
    }

    return $this;
}

/**
 * Export columns to schema file and remove them from manifest.
 */
protected function exportColumnsToSchema(string $tableName, array $columns): void
{
    $root = $this->manifest->root();
    $schemaPath = $root . '/Backend/Database/migrations/schemas/' . $tableName . '.json';

    // Use the SchemaExporter to create the schema file from the columns array
    SchemaExporter::exportFromArray($tableName, $columns, $schemaPath);

    // Now remove columns from manifest
    $this->removeColumnsFromManifest($tableName);
}

/**
 * Remove columns from the manifest for a specific table.
 */
protected function removeColumnsFromManifest(string $tableName): void
{
    $manifestPath = $this->manifest->path() . '/manifest.json';
    $existingManifest = ManifestFactory::loadManifest($manifestPath);

    $tables = $existingManifest['migrations']['tables'] ?? [];

    foreach ($tables as &$table) {
        if ($table['name'] === $tableName) {
            unset($table['columns']);
            break;
        }
    }

    $existingManifest['migrations']['tables'] = $tables;
    ManifestFactory::update($existingManifest, ['migrations' => $existingManifest['migrations']], $manifestPath);
}

/**
 * Generate PHP code for migration columns from a schema array.
 */
protected function generateColumnDefinitions(array $columns): string
{
    if (empty($columns)) {
        return '';
    }

    $lines = [];
    foreach ($columns as $col) {
        $name = $col['name'];
        $type = $col['type'];

        $line = "\$table->{$type}('{$name}'";

        // Add length for string
        if ($type === 'string' && isset($col['length']) && $col['length'] !== null && $col['length'] != 255) {
            $line .= ", {$col['length']}";
        }

        // Add precision/scale for decimal
        if ($type === 'decimal' && isset($col['precision']) && isset($col['scale'])) {
            $line .= ", {$col['precision']}, {$col['scale']}";
        }

        $line .= ')';

        // Modifiers
        if ($col['nullable'] ?? false) {
            $line .= '->nullable()';
        }
        if ($col['unique'] ?? false) {
            $line .= '->unique()';
        }
        if ($col['indexed'] ?? false) {
            $line .= '->index()';
        }
        if (isset($col['default']) && $col['default'] !== null && $col['default'] !== '') {
            if (in_array($type, ['string', 'text'])) {
                $line .= "->default('{$col['default']}')";
            } else {
                $line .= "->default({$col['default']})";
            }
        }
        if (isset($col['comment']) && $col['comment']) {
            $line .= "->comment('{$col['comment']}')";
        }

        // Foreign key constraint
        if ($type === 'foreignId' && isset($col['referenceTable']) && $col['referenceTable']) {
            $refCol = $col['referenceColumn'] ?? 'id';
            $line .= "->constrained('{$col['referenceTable']}', '{$refCol}')";
        }

        $lines[] = '            ' . $line . ';';
    }

    return implode("\n", $lines);
}


    public function updateMigrations(): BackendStub
    {
        $migrationPath = $this->backendPath . 'Database/migrations';
        $relativePath  = $this->module['root'] . DIRECTORY_SEPARATOR . 'Backend/Database/migrations';
        $seedersPath   = $this->backendPath . 'Database/Seeders';

        if (!File::exists($migrationPath)) {
            return $this;
        }



        $files = File::files($migrationPath);

        foreach ($files as $file) {

            $filepath = $file->getPathname();

            // Load Migration Class File
            require_once $filepath;

            // Normalize Filename → Class Name
            $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $schemaFileName = $filename;

            // Remove Laravel timestamp prefix
            $filename = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);

            $className = \Illuminate\Support\Str::studly($filename);

            if (!class_exists($className)) {
                continue;
            }

            $migration = new $className();



            if (!$migration instanceof \SchoolPalm\ModuleBridge\Database\BaseMigration) {
                continue;
            }

            if (!method_exists($migration, 'tableName')) {
                continue;
            }

            $tableName = $migration->tableName();

            if (\Illuminate\Support\Facades\Schema::hasTable($tableName)) {


                if (method_exists($migration, 'update')) {
                    $migration->update();
                }

                if (method_exists($migration, 'drop')) {
                    $migration->drop();
                }

                // 🔹 Export the schema and get it directly
                $schemaJsonPath = $relativePath . DIRECTORY_SEPARATOR . 'schemas' . DIRECTORY_SEPARATOR . $schemaFileName . '.json';
                $schemaData     = SchemaExporter::export(
                    $tableName,
                    $relativePath . DIRECTORY_SEPARATOR . $schemaFileName . '.php',
                    $schemaJsonPath
                );


                // 🔹 Seeder regeneration using $schemaData
                if ($schemaData && isset($schemaData['table'])) {

                    $seederClassName = \Illuminate\Support\Str::studly($schemaData['table']) . 'Seeder';
                    $seederFilePath  = $seedersPath . DIRECTORY_SEPARATOR . $seederClassName . '.php';


                    // Delete old seeder
                    if (File::exists($seederFilePath)) {
                        File::delete($seederFilePath);
                    }


                    // Generate new seeder
                    $seederGenerator = new \SchoolPalm\ModuleSDK\Generators\SeederGenerator();
                    $seederNamespace = $this->module['namespace'] . '\\Database\\Seeders';

                    $seederGenerator->generate($schemaData, $seederFilePath, $seederNamespace);
                }
            }
        }

        return $this;
    }




    public function dropAllTables(): BackendStub
    {
        $migrationPath = $this->backendPath . 'Database/migrations';

        if (!File::exists($migrationPath)) {
            return $this;
        }

        $files = File::files($migrationPath);

        foreach ($files as $file) {

            $filepath = $file->getPathname();

            /*
        |--------------------------------------------------------------------------
        | Load Migration Class File
        |--------------------------------------------------------------------------
        */

            require_once $filepath;

            /*
        |--------------------------------------------------------------------------
        | Normalize Filename → Class Name
        |--------------------------------------------------------------------------
        */

            $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $migrationName = $filename;

            // Remove Laravel timestamp prefix
            $filename = preg_replace(
                '/^\d{4}_\d{2}_\d{2}_\d{6}_/',
                '',
                $filename
            );

            // Convert to StudlyCase class name
            $className = \Illuminate\Support\Str::studly($filename);

            /*
        |--------------------------------------------------------------------------
        | Resolve Class
        |--------------------------------------------------------------------------
        */

            if (!class_exists($className)) {
                continue;
            }

            $migration = new $className();

            if (!$migration instanceof \SchoolPalm\ModuleBridge\Database\BaseMigration) {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | Drop Table If Exists
        |--------------------------------------------------------------------------
        */

            $migration->down($migrationName);
        }

        return $this;
    }

    public function listeners(): BackendStub
    {
        $stubPath   = $this->stubPath . '/' . $this->stubMap['listeners'];
        $targetPath = $this->backendPath . 'Listeners/' . $this->stubMap['listeners'];

        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    public function moveAssets(): BackendStub
    {
        $sourcePath = $this->stubRootPath . DIRECTORY_SEPARATOR . 'assets';
        $targetPath = $this->rootPath . DIRECTORY_SEPARATOR . 'assets';

        if (!File::exists($sourcePath)) {
            return $this;
        }

        // Copy entire directory recursively
        File::copyDirectory($sourcePath, $targetPath);

        return $this;
    }

    public function readme(): BackendStub
    {

        $stubPath   = $this->stubPath . '/' . $this->stubMap['readme'];
        $targetPath = $this->backendPath . '/' . $this->stubMap['readme'];
        $data = [
            'MODULE_NAME' => $this->module['name'],
            'VENDOR_NAME' => $this->module['vendor'],
            'VENDOR_EMAIL' => $this->manifest->info()->author()->email() ?? '',
            'VENDOR_WEBSITE' => $this->manifest->info()->author()->website() ?? '',
            'YEAR' => today()->format('Y'),
            'LICENSE_TYPE' => $this->manifest->info()->licenseType(),
            'MODULE_SHORT_DESCRIPTION' => $this->manifest->info()->description()
        ];
        $this->publishStub($stubPath, $targetPath, $data);

        return $this;
    }

    public function license(): BackendStub
    {

        $stubPath   = $this->stubPath . '/' . $this->stubMap['license'];
        $targetPath = $this->backendPath . '/' . $this->stubMap['license'];
        $data = [
            'VENDOR_NAME' => $this->module['vendor'],
            'VENDOR_EMAIL' => $this->manifest->info()->author()->email() ?? '',
            'VENDOR_WEBSITE' => $this->manifest->info()->author()->website() ?? '',
            'YEAR' => today()->format('Y')
        ];
        $this->publishStub($stubPath, $targetPath, $data);

        return $this;
    }

    public function tests(): BackendStub
    {
        $stubPath   = $this->stubPath . '/' . $this->stubMap['tests'];
        $targetPath = $this->backendPath . 'Tests/' . $this->stubMap['tests'];

        $this->publishStub($stubPath, $targetPath);
        return $this;
    }

    /**
     * Generate everything
     */
    public function all(): BackendStub
    {
        CreatedRegistry::forgetKey($this->module_key, 'relations');
        return $this
            ->main()
            ->module()
            ->contracts()
            ->createRelationClass()
            ->dtos()
            ->actions()
            ->providers()
            ->migrations()
            ->generateDtosFromSchemas()
            ->events()
             ->relations()
           ->models()
            ->listeners()
            ->license()
            ->readme()
            ->moveAssets()
            ->tests();
    }


    public function addActions(array $actions): BackendStub
    {
       // $className = Str::studly($this->manifest->info()->name()) . 'Actions';
       // $file  =  $this->backendPath . DIRECTORY_SEPARATOR . 'Actions' . DIRECTORY_SEPARATOR . $className . '.php';
      //  $stub  =  new ActionGenerator($file, $actions);
       // $stub->generate();
        return $this;
    }

    public function addAction(
        array $action,
        string $type,
        string $dto
    ): BackendStub {

        $className = Str::studly(
            $this->manifest->info()->name()
        ) . 'Actions';

        $file =
            $this->backendPath
            . DIRECTORY_SEPARATOR
            . 'Actions'
            . DIRECTORY_SEPARATOR
            . $className
            . '.php';

        (new ActionScaffoldGenerator(
            $file,
            $action,
            $type,
            $dto
        ))->generate($this->manifest);

        return $this;
    }



    /**
 * Generate a full CRUD resource from a DTO.
 *
 * Accepts either a short DTO name (e.g., "StudentData") or a fully qualified class name.
 * If short, it resolves it using the module's DTO namespace from the manifest.
 *
 * @param string $dto DTO class name (short or FQCN)
 * @return $this
 * @throws \RuntimeException if the DTO class does not exist
 */
public function generateResource(string $dto): BackendStub
{
   
    // If it's not a FQCN (no backslash), build it from the manifest namespace
    if (!str_contains($dto, '\\')) {
        $dtoFqcn = $this->manifest->info()->namespace() . '\\DTOs\\' . $dto;
    } else {
        $dtoFqcn = $dto;
    }

    if (!class_exists($dtoFqcn)) {
        throw new \RuntimeException("DTO class not found: {$dtoFqcn}");
    }

    $className = Str::studly($this->manifest->info()->name()) . 'Actions';
    $file = $this->backendPath . 'Actions/' . $className . '.php';

    $generator = new ResourceGenerator($file, $dtoFqcn, $this->manifest);
    $generator->generate();

    return $this;
}
}
