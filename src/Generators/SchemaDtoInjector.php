<?php

namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Facades\File;

class SchemaDtoInjector
{
    public function generate(
        string $schemaPath,
        string $dtoPath,
        string $namespace,
        string $dtoName
    ): void {

        if (!File::exists($schemaPath)) {
            throw new \RuntimeException("Schema file not found: {$schemaPath}");
        }

        $schema = json_decode(file_get_contents($schemaPath), true);

        if (!$schema || !isset($schema['columns'])) {
            throw new \RuntimeException("Invalid schema format");
        }

        $columns = $schema['columns'];

        $properties  = $this->buildProperties($columns);
        $constructor = $this->buildConstructor($columns);
        $fromArray   = $this->buildFromArray($columns);
        $fromModel   = $this->buildFromModel($columns);
        $fromDTO     = $this->buildFromDTO($columns);
        $toDTO       = $this->buildToDTO($columns);
        $toArray     = $this->buildToArray($columns);

        $class = $this->buildClass(
            $namespace,
            $dtoName,
            $properties,
            $constructor,
            $fromArray,
            $fromModel,
            $fromDTO,
            $toDTO,
            $toArray
        );

        File::put($dtoPath, $class);
    }

    // -----------------------------------------------------
    // CLASS BUILDER
    // -----------------------------------------------------
    private function buildClass(
        string $namespace,
        string $dtoName,
        string $properties,
        string $constructor,
        string $fromArray,
        string $fromModel,
        string $fromDTO,
        string $toDTO,
        string $toArray
    ): string {

        return <<<PHP
<?php

namespace {$namespace};

class {$dtoName}
{
    {$properties}

{$fromArray}

{$fromModel}

{$fromDTO}

{$toDTO}

{$toArray}

{$constructor}
}
PHP;
    }

    // -----------------------------------------------------
    // IMMUTABLE PROPERTIES (SCALARS ONLY)
    // -----------------------------------------------------
    private function buildProperties(array $columns): string
    {
        return implode("\n    ", array_map(function ($col) {
            $type = $this->mapType($col['type']);
            return "public readonly ?{$type} \${$col['name']};";
        }, $columns));
    }

    // -----------------------------------------------------
    // CONSTRUCTOR
    // -----------------------------------------------------
    private function buildConstructor(array $columns): string
    {
        $params = [];
        $assign = [];

        foreach ($columns as $col) {

            $type = $this->mapType($col['type']);

            $params[] = "?{$type} \${$col['name']}";
            $assign[] = "\$this->{$col['name']} = \${$col['name']};";
        }

        return <<<PHP

    public function __construct(
        {$this->indent(implode(",\n        ", $params), 8)}
    ) {
        {$this->indent(implode("\n        ", $assign), 8)}
    }
PHP;
    }

    // -----------------------------------------------------
    // FROM ARRAY
    // -----------------------------------------------------
    private function buildFromArray(array $columns): string
    {
        $args = [];

        foreach ($columns as $col) {
            $args[] = "\$data['{$col['name']}'] ?? null";
        }

        return <<<PHP

    public static function fromArray(array \$data): self
    {
        return new self(
            {$this->indent(implode(",\n            ", $args), 12)}
        );
    }
PHP;
    }

    // -----------------------------------------------------
    // FROM MODEL
    // -----------------------------------------------------
    private function buildFromModel(array $columns): string
    {
        $args = [];

        foreach ($columns as $col) {
            $args[] = "\$model->{$col['name']} ?? null";
        }

        return <<<PHP

    public static function fromModel(\$model): self
    {
        return new self(
            {$this->indent(implode(",\n            ", $args), 12)}
        );
    }
PHP;
    }

    // -----------------------------------------------------
    // FROM DTO
    // -----------------------------------------------------
    private function buildFromDTO(array $columns): string
    {
        $args = [];

        foreach ($columns as $col) {
            $args[] = "\$dto->{$col['name']}";
        }

        return <<<PHP

    public static function fromDTO(self \$dto): self
    {
        return new self(
            {$this->indent(implode(",\n            ", $args), 12)}
        );
    }
PHP;
    }

    // -----------------------------------------------------
    // TO DTO (CLONE)
    // -----------------------------------------------------
    private function buildToDTO(array $columns): string
    {
        $args = [];

        foreach ($columns as $col) {
            $args[] = "\$this->{$col['name']}";
        }

        return <<<PHP

    public function toDTO(): self
    {
        return new self(
            {$this->indent(implode(",\n            ", $args), 12)}
        );
    }
PHP;
    }

    // -----------------------------------------------------
    // TO ARRAY
    // -----------------------------------------------------
    private function buildToArray(array $columns): string
    {
        $out = [];

        foreach ($columns as $col) {
            $out[] = "'{$col['name']}' => \$this->{$col['name']}";
        }

        return <<<PHP

    public function toArray(): array
    {
        return [
            {$this->indent(implode(",\n            ", $out), 12)}
        ];
    }
PHP;
    }

    // -----------------------------------------------------
    // SCALAR TYPE MAPPING ONLY
    // -----------------------------------------------------
    private function mapType(string $type): string
    {
        return match ($type) {

            // string types
            'string', 'text' => 'string',

            // integers
            'integer', 'bigInteger', 'smallInteger', 'mediumInteger' => 'int',

            // floats
            'float', 'double', 'decimal' => 'float',

            // boolean
            'boolean' => 'bool',

            // EVERYTHING ELSE FALLS BACK TO STRING
            'date', 'datetime' => 'string',

            default => 'string',
        };
    }

    // -----------------------------------------------------
    private function indent(string $text, int $spaces): string
    {
        $pad = str_repeat(' ', $spaces);
        return $pad . str_replace("\n", "\n" . $pad, $text);
    }
}