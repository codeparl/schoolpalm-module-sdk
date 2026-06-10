<?php
namespace SchoolPalm\ModuleSDK\Support;

use SchoolPalm\ModuleBridge\Support\DevPort;

class ModulePorts
{
    
    public static function get(string $module): array|null
    {
        return DevPort::get($module);
    }
}
