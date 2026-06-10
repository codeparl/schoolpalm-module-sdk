<?php

namespace SchoolPalm\ModuleSDK\Console;

use Illuminate\Support\Facades\File;
use SchoolPalm\ModuleSDK\Generators\SchemaDtoInjector;

class GenerateDtoFromSchemaCommand extends ModuleCommandBase
{
    protected $signature = 'module:dto {dto} {table}';

    protected $description = 'Generate or update DTO fields from schema JSON';

    public function handle(): int
    {
        // ---------------------------------------
        // 1. Select module dynamically
        // ---------------------------------------
        $module = $this->chooseModule(null);

        if (!$module) {
            $this->error('No module selected.');
            return self::FAILURE;
        }

        $this->info("Selected module: {$module['module']}");

        // ---------------------------------------
        // 2. Inputs
        // ---------------------------------------
        $dtoName = $this->argument('dto');
        $table = $this->argument('table');

        // ---------------------------------------
        // 3. Resolve paths dynamically
        // ---------------------------------------
        $schemaPath = $this->resolveSchemaPath($module, $table);
        $dtoPath = $this->resolveDtoPath($module, $dtoName);

      
        if (!$schemaPath) {
            $this->error("Schema not found for table: {$table}");
            return self::FAILURE;
        }

        if (!$dtoPath) {
            $this->error("DTO not found: {$dtoName}");
            return self::FAILURE;
        }

        // ---------------------------------------
        // 4. Inject schema into DTO
        // ---------------------------------------
        $injector = new SchemaDtoInjector();

        $injector->generate(
            $schemaPath,
            $dtoPath,
            $module['namespace'] . '\\DTOs',
            $dtoName
        );

        $this->info("DTO generated successfully.");

        return self::SUCCESS;
    }

    // =====================================================
    // Resolve schema file dynamically per module
    // =====================================================
    protected function resolveSchemaPath(array $module, string $table): ?string
    {
        $schemaDir = $module['path'] . '/Database/migrations/schemas';

     
        if (!File::exists($schemaDir)) {
            return null;
        }

       
        foreach (File::files($schemaDir) as $file) {
            if (str_ends_with($file->getFilename(), $table . '_table.json')) {
                return $file->getPathname();
            }
        }

        return null;
    }

    // =====================================================
    // Resolve DTO file dynamically per module
    // =====================================================
    protected function resolveDtoPath(array $module, string $dto): ?string
    {
        $dtoDir = $module['path'] . '/DTOs';

        if (!File::exists($dtoDir)) {
            return null;
        }

        

        foreach (File::files($dtoDir) as $file) {

            if (str_starts_with(strtolower($file->getFilename()),strtolower($dto))) {
                return $file->getPathname();
            }
        }

        return null;
    }

    // Optional helper if needed later
    protected function getNamespaceFromFile(string $filePath): string
    {
        $content = file_get_contents($filePath);

        preg_match('/namespace\s+(.+?);/', $content, $matches);

        return $matches[1] ?? '';
    }
}