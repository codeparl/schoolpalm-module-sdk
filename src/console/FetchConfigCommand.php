<?php

namespace SchoolPalm\ModuleSDK\Console;

use SchoolPalm\ModuleBridge\Facades\SdkConfig;

class FetchConfigCommand extends ModuleCommandBase
{
    protected $signature = 'sdk:fetch-config 
                            {--type=all : Config type (curricula, levels, roles, all)} 
                            {--refresh : Force refresh from API}';

    protected $description = 'Fetch SchoolPalm SDK configuration';

    public function handle(): int
    {
        $type = strtolower($this->option('type') ?? 'all');

        $this->info("🔄 Fetching SchoolPalm SDK configuration [{$type}]...");

        try {

            $types = $type === 'all'
                ? ['curricula', 'levels', 'roles']
                : [$type];

            foreach ($types as $t) {

                $this->line("➡️ Fetching: {$t}");

                $config = SdkConfig::type($t);

                $data = $this->option('refresh')
                    ? $config->refresh()
                    : $config->getConfig();

                if (empty($data)) {
                    $this->warn("⚠️ No data received for {$t}");
                    continue;
                }

                $this->line("✅ {$t} loaded: " . count($data));
            }

            $this->line('');
            $this->comment('📂 All configs cached locally.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Error fetching config: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
