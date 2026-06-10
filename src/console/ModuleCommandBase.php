<?php

namespace SchoolPalm\ModuleSDK\Console;

use Illuminate\Console\Command;
use SchoolPalm\ModuleBridge\Facades\CreatedRegistry;

class ModuleCommandBase extends Command
{
    /**
     * Ask the user to select a module from the registry.
     *
     * @return array|null Selected module data or null if cancelled/invalid
     */
    protected function chooseModule(?string $filter=null): ?array
    {

       $choices = CreatedRegistry::listForCLI($filter);
        if (empty($choices)) {
            $this->error('No modules found, create new modules.');
            return [];
        }
        // Ask the user to select
        $selectedNamespace = $this->choice(
            'Select a module',
            $choices
        );

        // Map back to numeric index
        $selectedId = array_search($selectedNamespace, $choices);

        if (!$selectedId) {
            $this->error('Invalid module selection.');
            return null;
        }

        // Return module from registry
        return CreatedRegistry::find($selectedId);
    }
}
