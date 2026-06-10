<?php

namespace SchoolPalm\ModuleSDK\Core;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleSDK\Helpers\Helper;

abstract class ActionResolver
{
    /** @var string|null The current portal (e.g. 'admin', 'teacher', 'student'). */
    public $root;

    /** @var string|null The current module name (e.g. 'students', 'subjects'). */
    protected $moduleName;

    /** @var string|null The current action (e.g. 'view-list', 'edit', 'create'). */
    protected $action;

    /** @var mixed|null Optional record ID (e.g. student ID). */
    public $id;

    /** @var string|null Absolute path to the current module directory. */
    protected $modulePath;

    /** @var \Illuminate\Http\Request The current HTTP request instance. */
    public $request;

    public $rootDir;

    public  array $module;
    /** @var array<string, object> Loaded module action classes (e.g. StudentActions). */
    protected array $actions = [];

    /**
     * BaseModule constructor.
     *
     * Initializes the module based on route segments and environment context.
     * Loads all available module action files automatically.
     */
    protected function __construct()
    {

        $this->request = request();
        $this->rootDir  =  app_path('SDK');

        $this->root =  Helper::getRouteSegment(key:'portal',mode:'sdk');

        $this->moduleName =  Helper::getRouteSegment(key:'module',mode:'sdk');
        $this->action =  Helper::getRouteSegment(key:'action',mode:'sdk');
        $this->id =  Helper::getRouteSegment(key:'id',mode:'sdk');

        // Fallback logic: if module or action segment is missing, use portal as default
        if (! $this->moduleName) {
            $this->moduleName = $this->root;
        }


        if (! $this->action) {
            $this->action = $this->moduleName;
        }




         $this->loadActions();
    }


    /**
     * Perform the current action.
     *
     * This method acts as the main entry point for executing module logic.
     * It will handle both normal and AJAX requests seamlessly.
     *
     * @return mixed
     */
    public function performAction()
    {
        // Currently both AJAX and non-AJAX routes delegate to handleActions()
        return $this->handleActions();
    }

    /**
     * Generate the method name to execute for the current action.
     *
     * Converts an action like 'view-list' → 'runViewList'
     * or 'add-student' → 'runAddStudent'.
     *
     * @param string $prefix Optional prefix for the method (default: 'run')
     * @return string The resolved method name
     */
    public function getMethodName(string $prefix = 'run'): string
    {
        return $prefix . preg_replace(
            '/\s+/',
            '',
            Str::title(preg_replace('/\-+/', ' ', $this->action))
        );
    }

    /**
     * Handle the current action by invoking the appropriate method.
     *
     * The method first checks if the action exists in the BaseModule,
     * then in any dynamically loaded action submodules.
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handleActions()
    {
        $method = $this->getMethodName();

        if (method_exists($this, $method)) {

            $ref = new \ReflectionMethod($this, $method);
            $params = $ref->getParameters();

            if (count($params) > 0) {
                return $this->{$method}($this->id);
            }
            return $this->{$method}();
        }

        // Attempt to call a method on a loaded action module (existing behavior)
        foreach ($this->actions as $actionClass) {

            if (method_exists($actionClass, $method)) {
                $ref = new \ReflectionMethod($actionClass, $method);
                $params = $ref->getParameters();
                // Prepend id if the called method expects parameters
                if (count($params) > 0) {
                    return $actionClass->{$method}($this->id);
                }

                return $actionClass->{$method}();
            }
        }

        // If not found, abort with 403
        return abort(403, 'No action defined.');
    }


    /**
     * Automatically load all module-specific action classes.
     *
     * This looks into the module’s "Actions" directory and loads
     * any PHP classes found there, registering them for delegation.
     *
     * Example path:
     * app/Custom/Packages/Secondary/Modules/Students/Actions/
     *
     * @return void
     */
   protected function loadActions(): void
{
    $this->actions = [];

    // Path and namespace
    $actionPath = $this->rootDir . '/Actions';
    $actionNamespace = 'App\\SDK\\Actions';

    if (!is_dir($actionPath)) {
        return;
    }

    foreach (File::files($actionPath) as $file) {

        // Get filename without extension
        $className = $file->getFilenameWithoutExtension();

        // Build full class name
        $class = $actionNamespace . '\\' . $className;

        if (class_exists($class)) {

            $this->actions[$className] = new $class($this);

        }
    }
}



    /**
     * Magic method to handle calls to undefined methods.
     *
     * Delegates method calls to submodules (action classes)
     * if the called method exists there.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     *
     * @throws \BadMethodCallException If method not found anywhere
     */
    public function __call($method, $args)
    {

        foreach ($this->actions as $action) {
            if (method_exists($action, $method)) {
                $ref = new \ReflectionMethod($action, $method);
                $params = $ref->getParameters();
                // If method expects at least 1 param, auto inject ID
                if (count($params) > 0) {
                    array_unshift($args, $this->id);
                }

                return $action->$method(...$args);
            }
        }

        throw new \BadMethodCallException("Method {$method} not found");
    }


    /**
     * Resolve the Inertia component path for the current module.
     *
     * This helps the controller dynamically determine which Vue component
     * should render for the requested action. If the component does not exist,
     * it falls back to a common component.
     *
     * @return string The relative Vue component path.
     */
    public function componentPath(): string
    {
        return Str::studly($this->action);
    }

    /**
     * Get the referer component path (tenant-aware or central).
     *
     * Useful when navigating back or linking to another component dynamically.
     *
     * @return string
     */
    public function refererComponent(): string
    {
        return '';
    }

    /**
     * Build a module component path with an optional subpath appended.
     *
     * @param string $path Optional relative subpath
     * @return string The concatenated module component path
     */
    public function moduleComponentPath(string $path = ''): string
    {
        return '';
    }
}
