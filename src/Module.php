<?php

namespace SchoolPalm\ModuleSDK;

use SchoolPalm\ModuleSDK\Contracts\SchoolPalmModule;

abstract class Module implements SchoolPalmModule
{
    public function version(): string
    {
        return '1.0.0';
    }

    public function boot(): void
    {
        //
    }

    public function menus(): array
    {
        return [];
    }

    public function permissions(): array
    {
        return [];
    }
}
