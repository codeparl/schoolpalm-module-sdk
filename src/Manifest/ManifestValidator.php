<?php

namespace SchoolPalm\ModuleSDK\Manifest;

use Illuminate\Console\Command;
use Opis\JsonSchema\Validator;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\ValidationResult;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

class ManifestValidator
{
    public static function validate(array $manifest,$command=null): array
    {
        $schemaPath = ModulePaths::schemaPath();

        if (!file_exists($schemaPath)) {
            throw new RuntimeException('Module manifest schema not found at: ' . $schemaPath);
        }

        $schemaJson = json_decode(file_get_contents($schemaPath), true);

        if (!$schemaJson) {
            throw new RuntimeException('Invalid schema JSON');
        }

        ManifestFactory::normalizeJson($manifest, $schemaJson);
        $validator = new Validator();
        $validator->setMaxErrors(100);

        $validator->resolver()->registerFile(
            'https://schoolpalm.dev/schemas/module-manifest.json',
            $schemaPath
        );


        // Convert array to object
        $data = json_decode(json_encode($manifest));
        /** @var ValidationResult $result */
        $result = $validator->validate(
            $data,
            'https://schoolpalm.dev/schemas/module-manifest.json'
        );

        $formatter = new ErrorFormatter();

        if ($result->isValid()) {
            return [];
        }

        if($command == null){
            throw ValidationException::withMessages(
            $formatter->format($result->error())
        );
        }

        // Custom formatting using object property access
        $custom = function (ValidationError $error) {
            $dataInfo = $error->data();
            $schemaInfo = $error->schema()->info()->data();

            $keyword = $error->keyword();

            // Use object property access for stdClass
            $customMessage = isset($schemaInfo->{'$error'}) && isset($schemaInfo->{'$error'}->{$keyword})
                ? $schemaInfo->{'$error'}->{$keyword}
                : null;

            return [
                'path' => implode('.', $dataInfo->fullPath()) ?: '(root)',
                'message' => $customMessage ?: $error->message(),
                'keyword' => $keyword,
            ];
        };

        $errors = $formatter->format($result->error(), true, $custom);

        if($command instanceof Command){
            $command->error("❌ Manifest validation failed.");
        }else
        echo "✖ Manifest validation failed:\n";

        $errorBug = [];
        foreach ($errors as $errorGroup) {
            foreach ($errorGroup as $error) {
                $path = $error['path'] ?? '(root)';
                $message = $error['message'] ?? 'Unknown error';
                echo "   • {$path}: {$message}\n";
                $errorBug[] = " {$path}: {$message}";
            }
        }

        return $errorBug;
    }
}
