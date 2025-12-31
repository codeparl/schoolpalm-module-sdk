<?php
class InstallModuleCommand extends Command
{
    protected $signature = 'sp:install-m {module}';
    protected $description = 'Install a module into the host Laravel app';

    public function handle()
    {
        $moduleName = $this->argument('module');

        // 1. Validate module exists in Modules folder
        $modulePath = base_path("Modules/{$moduleName}");
        if (!is_dir($modulePath)) {
            $this->error("Module {$moduleName} not found.");
            return;
        }

        // 2. Read manifest.json
        $manifest = Helper::loadJson($modulePath . '/manifest.json');

        // 3. Copy files into host app (resources/views, public, config)
        $this->publishModuleFiles($modulePath, $manifest);

        // 4. Install Composer dependencies if defined
        $this->installDependencies($manifest['dependencies'] ?? []);

        // 5. Run migrations if any
        $this->runMigrations($manifest['migrations'] ?? []);

        // 6. Register module in SDK runtime
        ModuleRegistry::register($manifest['module_key'], $manifest);

        $this->info("Module {$moduleName} installed successfully.");
    }
}
