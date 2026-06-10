<?php

namespace SchoolPalm\ModuleSDK\Core;

use SchoolPalm\ModuleBridge\Facades\CreatedRegistry;

class ModuleManager{

private array $module;
    public function __construct(private string $module_key)
    {
    $this->module = CreatedRegistry::get($this->module_key);

    }


    public function shouldRun(bool $value){
    CreatedRegistry::update($this->module_key,['run'=> $value]);
    }

  

}