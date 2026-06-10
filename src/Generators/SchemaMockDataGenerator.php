<?php

namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;
use SchoolPalm\ModuleBridge\Support\Helper;

class SchemaMockDataGenerator
{
    protected $faker;

    public function __construct()
    {
        $this->faker = FakerFactory::create();
    }

    public function generate(
        array $providedContracts,
        string $schemasPath,
        string $outputPath,
        int $rows = 10
    ): void {

        if (!is_dir($schemasPath)) {
            throw new \RuntimeException("Schemas path not found");
        }

        File::ensureDirectoryExists($outputPath);

        foreach ($providedContracts as $contract) {

            $schemaFile = class_basename($contract);
            $schemaPath = $schemasPath . '/' . $schemaFile.'.json';
            if (!is_file($schemaPath)) {
                throw new \RuntimeException("Schema missing: {$schemaFile}");
            }

            $schema = Helper::loadJson($schemaPath);

            if (!$schema || !isset($schema['columns'])) {
                throw new \RuntimeException("Invalid schema: {$schemaFile}");
            }

            $data = $this->generateRows($schema['columns'], $rows);
            $fileName = Str::studly($this->resolveEntity($contract)) . '.json';
            Helper::storeJson(  $outputPath . '/' . $fileName,$data);

        }
    }

    /**
     * Extract entity from contract
     */
    protected function resolveEntity(string $contract): string
    {
        return   preg_replace('/Contract$/', '', class_basename($contract));
    }

    /**
     * Generate rows using Faker
     */
    protected function generateRows(array $columns, int $rows): array
    {
        $data = [];

        for ($i = 1; $i <= $rows; $i++) {

            $row = [];

            foreach ($columns as $col) {

                $row[$col['name']] = $this->fakeValue($col, $i);
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     * Faker-powered value generator
     */
    protected function fakeValue(array $column, int $index)
    {
        $name = $column['name'];
        $type = $column['type'];

        return match ($type) {

            'bigInteger', 'integer', 'smallInteger' =>
                $column['primary']
                    ? $index
                    : $this->faker->numberBetween(1, 1000),

            'string' => $this->fakeString($name),

            'text' => $this->faker->sentence(12),

            'date' => $this->faker->date(),

            'datetime' => $this->faker->dateTime()->format('Y-m-d H:i:s'),

            'boolean' => $this->faker->boolean(),

            default => $this->faker->word(),
        };
    }

    protected function normalizeColumnName(string $name): string
{
    return Str::snake($name);
}
    /**
     * Smart field-aware string generation
     */
  protected function fakeString(string $name): string
{
    $name = $this->normalizeColumnName($name);

    return match (true) {

        // =========================
        // PERSONAL INFO
        // =========================
        str_contains($name, 'first_name') => $this->faker->firstName(),
        str_contains($name, 'last_name')  => $this->faker->lastName(),
        str_contains($name, 'middle_name') => $this->faker->firstName(),
        str_contains($name, 'full_name')   => $this->faker->name(),

        // =========================
        // CONTACT INFO
        // =========================
        str_contains($name, 'email')       => $this->faker->unique()->safeEmail(),
        str_contains($name, 'phone')       => $this->faker->phoneNumber(),
        str_contains($name, 'telephone')   => $this->faker->phoneNumber(),
        str_contains($name, 'mobile')      => $this->faker->phoneNumber(),
        str_contains($name, 'address')     => $this->faker->address(),

        // =========================
        // LOCATION
        // =========================
        str_contains($name, 'city')        => $this->faker->city(),
        str_contains($name, 'state')       => $this->faker->state(),
        str_contains($name, 'country')     => $this->faker->country(),
        str_contains($name, 'street')      => $this->faker->streetAddress(),
        str_contains($name, 'location')    => $this->faker->address(),

        // =========================
        // SCHOOL / SYSTEM DOMAIN
        // =========================
        str_contains($name, 'school_name') => $this->faker->company(),
        str_contains($name, 'institution') => $this->faker->company(),
        str_contains($name, 'class_name')  => 'Class ' . $this->faker->randomLetter(),
        str_contains($name, 'subject')     => $this->faker->randomElement([
            'Mathematics',
            'Physics',
            'Chemistry',
            'Biology',
            'English',
            'History',
            'Geography'
        ]),

        // =========================
        // IDENTIFIERS / SYSTEM
        // =========================
        str_contains($name, 'username')    => $this->faker->userName(),
        str_contains($name, 'password')    => $this->faker->password(8, 16),
        str_contains($name, 'code')        => strtoupper($this->faker->bothify('??###')),
        str_contains($name, 'reference')   => strtoupper($this->faker->bothify('REF-####')),
        str_contains($name, 'uuid')        => $this->faker->uuid(),

        // =========================
        // GENERIC TEXT FALLBACKS
        // =========================
        str_contains($name, 'title')       => $this->faker->sentence(3),
        str_contains($name, 'description') => $this->faker->paragraph(),
        str_contains($name, 'note')        => $this->faker->sentence(8),
        str_contains($name, 'comment')     => $this->faker->sentence(10),

        // =========================
        // DEFAULT FALLBACK
        // =========================
        default => $this->faker->word(),
    };
}
}
