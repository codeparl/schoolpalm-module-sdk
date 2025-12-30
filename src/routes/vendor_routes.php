<?php
use Illuminate\Support\Facades\Route;
use SchoolPalm\ModuleSDK\Http\ModuleController;

Route::prefix('/')->group(function () {
    Route::match(
        ['get', 'post', 'patch', 'delete', 'put'],
        '{portal}/{module?}/{action?}/{id?}',
        [ModuleController::class, 'handle']
    );
});
