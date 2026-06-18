<?php

namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeederGenerator
{
    /**
     * Generate a seeder for a table schema
     *
     * @param array $schema Schema data from SchemaExporter
     * @param string $outputPath File path to save the seeder
     * @param string $namespace Seeder namespace
     * @param int|null $rows Number of fake rows to generate (default from config or 10)
     */
    public function generate(array $schema, string $outputPath, string $namespace, ?int $rows = null): void
    {
        $table = $schema['table'];
        $className = Str::studly($table) . 'Seeder';

        $rowsCount = $rows ?? config('sdk.seed_count', 10);

        $allRowsCode = [];

        for ($i = 0; $i < $rowsCount; $i++) {
            $fields = [];

            foreach ($schema['columns'] as $column) {
                $name = $column['name'];
                $type = $column['type'];

                // Skip auto increment id
                if ($name === 'id' || $column['autoincrement']) {
                    continue;
                }

                $faker = $this->fakerByType($name, $type, $column);
                $fields[] = "'{$name}' => {$faker}";
            }

            // Timestamps
            if (!empty($schema['timestamps'])) {
                $fields[] = "'created_at' => now()";
                $fields[] = "'updated_at' => now()";
                

            }

            $allRowsCode[] = '[' . implode(', ', $fields) . ']';
        }

        $rowsString = implode(",\n            ", $allRowsCode);

        $template = <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class {$className} extends Seeder
{
    public function run(): void
    {
        DB::table('{$table}')->truncate();

        DB::table('{$table}')->insert([
            {$rowsString}
        ]);
    }

    public function getTableName(): string
    {
        return '{$table}';
    }
}
PHP;

        File::put($outputPath, $template);
    }

    /**
     * Returns faker value based on column name/type
     */
    protected function fakerByType(string $columnName, string $type, array $column = []): string
    {
        // Always use current school session for school_id
        if ($columnName === 'school_id') {
            return 'session(config(\'sdk.current_school_session_key\'), 1)';
        }

        // Other ids → random
        if (Str::endsWith($columnName, 'id')) {
            return 'fake()->numberBetween(1, 50)';
        }

        if (Str::contains($columnName, 'email')) {
            return 'fake()->safeEmail()';
        }

        if (Str::contains($columnName, 'phone')) {
            return 'fake()->phoneNumber()';
        }

        if (Str::contains($columnName, 'name')) {
            return 'fake()->name()';
        }

        if (Str::contains($columnName, 'address')) {
            return 'fake()->address()';
        }

        if (Str::contains($columnName, 'date')) {
            return 'fake()->date()';
        }

        if ($type === 'json') {
            if (!empty($column['default'])) {
                $decoded = json_decode($column['default'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return var_export($decoded, true);
                }
            }

            return "[
                'key' => fake()->word(),
                'value' => fake()->sentence()
            ]";
        }

        // Fallback by type
        return match ($type) {
            'bigInteger', 'integer' => 'fake()->numberBetween(1, 100)',
            'string', 'varchar' => 'fake()->word()',
            'text' => 'fake()->sentence()',
            'boolean' => 'fake()->boolean()',
            'decimal', 'float', 'double' => 'fake()->randomFloat(2, 1, 1000)',
            'date' => 'fake()->date()',
            'datetime', 'timestamp' => 'fake()->dateTime()',
            default => 'fake()->word()',
        };
    }

    /**
     * Run all seeders in a module's Seeders folder
     */
    public static function runSeeders(string $backendPath, string $moduleNamespace): void
    {
        $seedersPath = rtrim($backendPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Seeders';
        $seedersNamespace = rtrim($moduleNamespace, '\\') . '\\Database\\Seeders';


        if (!File::exists($seedersPath)) {
            return; // no seeders folder
        }

        $files = File::files($seedersPath);

        foreach ($files as $file) {
            $seederClass = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $fullClass = $seedersNamespace . '\\' . $seederClass;

            if (!class_exists($fullClass)) {
                continue;
            }

            $seederInstance = app($fullClass);


            if (!method_exists($seederInstance, 'run')) {
                continue;
            }

            // Truncate table
            if (method_exists($seederInstance, 'getTableName')) {
                $table = $seederInstance->getTableName();
                if (Schema::hasTable($table)) {
                    Schema::disableForeignKeyConstraints();
                    DB::table($table)->truncate();
                    Schema::enableForeignKeyConstraints();
                }
            }

            // Run seeder
            $seederInstance->run();
        }
    }
}
