<?php

namespace SchoolPalm\ModuleSDK\Console;

use Illuminate\Support\Facades\File;
use SchoolPalm\ModuleSDK\Core\ModuleRegistry;

class GenerateServicePipelineCommand  extends ModuleCommandBase
{
    protected $signature = 'sp:pipeline';
    protected $description = 'Generate service pipeline (contract + service class) for a registered module';

    protected ModuleRegistry $registry;

    public function handle(): int
    {
        /* -------------------------------------------------------------
         | Choose module from registry
         |-------------------------------------------------------------*/
         $module = $this->chooseModule();
        if (!$module) {
            $this->error('Module not found in registry.');
            return self::FAILURE;
        }


        /* -------------------------------------------------------------
         | Infer service/contract names from module
         |-------------------------------------------------------------*/
        $serviceName  = $module['module'] . 'Service';
        $contractName = $module['module'] . 'Contract';

        $contractNamespace = $module['namespace'] . '\\Contracts';
        $serviceNamespace  = $module['namespace'] . '\\Services';

        $contractPath = $module['path'] . '/Contracts/' . $contractName . '.php';
        $servicePath  = $module['path'] . '/Services/' . $serviceName . '.php';

        /* -------------------------------------------------------------
         | Ensure folders exist
         |-------------------------------------------------------------*/
        foreach ([$module['path'] . '/Contracts', $module['path'] . '/Services'] as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }

        /* -------------------------------------------------------------
         | Generate contract interface
         |-------------------------------------------------------------*/
        $contractTemplate = <<<PHP
<?php

namespace {$contractNamespace};

interface {$contractName}
{
    // Define service methods here
}
PHP;
        File::put($contractPath, $contractTemplate);

        /* -------------------------------------------------------------
         | Generate service class
         |-------------------------------------------------------------*/
        $serviceTemplate = <<<PHP
<?php

namespace {$serviceNamespace};

use {$contractNamespace}\\{$contractName};

class {$serviceName} implements {$contractName}
{
    // Implement service methods here
}
PHP;
        File::put($servicePath, $serviceTemplate);

        /* -------------------------------------------------------------
         | Output
         |-------------------------------------------------------------*/
        $this->info("✅ Service pipeline generated for module: {$module['module_key']}");
        $this->line("• Contract: {$contractPath}");
        $this->line("• Service: {$servicePath}");

        return self::SUCCESS;
    }
}
