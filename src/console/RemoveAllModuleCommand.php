<?php

namespace SchoolPalm\ModuleSDK\Console;

use Illuminate\Support\Facades\File;
use SchoolPalm\ModuleBridge\Core\CreatedModuleRegistry;
use SchoolPalm\ModuleSDK\Core\ModuleRegistry;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

class RemoveAllModuleCommand extends ModuleCommandBase
{
    protected $signature = 'sp:clear-m {--force : Skip confirmation}';
    protected $description = 'List and remove ALL modules from SDK';

    public function handle(): int
    {
        $registry = new CreatedModuleRegistry(ModulePaths::registryFile());
        $modules  = $registry->all();

        if (empty($modules)) {
            $this->warn('No modules found in registry.');
            return self::SUCCESS;
        }
        $this->newLine();
        // -------------------- LIST MODULES (CLI STYLE) --------------------
        $this->info('📦 Modules registered in SDK:');
        $this->newLine();

        foreach ($modules as $i => $module) {
            $index = $i + 1;
            $this->line("  [{$index}] {$module['namespace']}");
        }

        $this->newLine();

        // -------------------- CONFIRM --------------------
        if (! $this->option('force')) {
            if (! $this->confirm(
                '⚠️ This will permanently DELETE ALL modules listed above. Continue?',
                false
            )) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        // -------------------- DELETE MODULE FOLDERS --------------------
        foreach ($modules as $module) {
            $path = $module['path'] ?? null;

            if (! $path) {
                continue;
            }

            if (! File::exists($path)) {
                $this->warn("Folder not found: {$path}");
                continue;
            }

            File::deleteDirectory($path);
            $this->info("🗑 Deleted: {$module['namespace']}");
        }

        $registry->removeAll();

     

        $this->info('✅ All modules removed successfully.');

        return self::SUCCESS;
    }
}
