<?php
namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class StubGenerator
{
    protected array $moduleData;
    protected array $stubPaths;

    public  function __construct()
    {

    }

    /**
     * Generate PHP stub file
     */
    public static function generate(array $moduleData, array $stubPaths): void
{
    foreach ($stubPaths as $stubPath => $filePath) {
        self::publishStub($stubPath, $filePath, $moduleData);
    }
}


    /**
     * Generate Vue stub file
     */
    public static function createVueStub(string $action = 'Index'): void
    {

    }

    /**
     * Load stub, replace placeholders, and write to target
     */
public  static function publishStub(string $stubPath, string $targetPath, array $moduleData = [], ?string $type = 'php'): void
{
    // Ensure the stub file exists
    if (!File::exists($stubPath)) {
        throw new \RuntimeException("Stub missing: {$stubPath}");
    }

    // Load stub content
    $content = File::get($stubPath);

    // Replace placeholders dynamically from moduleData
    foreach ($moduleData as $key => $value) {
        $content = str_replace("{{ {$key} }}", $value, $content);
    }

    // Ensure the target directory exists
    $dir = dirname($targetPath);
    if (!File::isDirectory($dir)) {
        File::makeDirectory($dir, 0755, true);
    }

    // Save the populated stub
    File::put($targetPath, $content);
}


}
