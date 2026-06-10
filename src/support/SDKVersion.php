<?php

namespace SchoolPalm\ModuleSDK\Support;

class SDKVersion{
private  static  string $version  = 'V1.5.0';

public static function getVersion(){
return self::$version;
}
}