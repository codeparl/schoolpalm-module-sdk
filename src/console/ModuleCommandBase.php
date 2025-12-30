<?php

namespace SchoolPalm\ModuleSDK\Console;

use Illuminate\Console\Command;
use SchoolPalm\ModuleSDK\Core\ModuleRegistry;

class ModuleCommandBase extends Command
{
    /**
     * Ask the user to select a module from the registry.
     *
     * @return array|null Selected module data or null if cancelled/invalid
     */
    protected function chooseModule(): ?array
    {


        $register  = app(ModuleRegistry::class);
       $choices = $register->listForCLI();
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
        return $register->find($selectedId);
    }
}
