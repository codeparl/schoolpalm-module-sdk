<?php
namespace SchoolPalm\ModuleSDK\Generators;

use SchoolPalm\ModuleSDK\Console\ModuleCommandBase;

class StubGenerator
{
 public static  function frontend(string $module_key, ?ModuleCommandBase $cmd=null){
    return new FrontendStub($module_key,$cmd);
 }

public static function backend(string $module_key,?ModuleCommandBase $cmd=null){
return new BackendStub($module_key,$cmd);
 }
    

}
