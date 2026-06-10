<?php
namespace SchoolPalm\ModuleSDK\Facades;

use Illuminate\Support\Facades\Facade;
/**
 * @method static string getMenus()
 */
class Menu extends Facade{


    public static function getFacadeAccessor():string
    {
        return 'menu.manager';
    }
}