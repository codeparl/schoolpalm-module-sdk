<?php

namespace SchoolPalm\ModuleSDK\Core;

use Illuminate\Support\Facades\File;
use SchoolPalm\ModuleBridge\Core\AbstractModule;
use SchoolPalm\ModuleBridge\Contracts\ResolverContract;

/**
 * Class BaseModule
 *
 * Base runtime module used inside the **Module SDK**.
 *
 * This class mimics SchoolPalm’s module execution environment
 * so vendors can develop and test modules locally.
 *
 * Responsibilities:
 * - Receive execution context from the SDK (portal, module, action, id)
 * - Load action classes from the module's Actions directory
 * - Delegate execution to Main or Action classes
 * - Resolve UI components via a Resolver implementation
 *
 * IMPORTANT:
 * - This class does NOT access request(), routes, tenants, or schools
 * - All runtime state is injected via setContext()
 *
 * @package SchoolPalm\ModuleSDK\Core
 */
class BaseModule extends AbstractModule
{
    /**
     * Loaded action handler instances.
     *
     * @var array<string, object>
     */
    protected array $modules = [];

    /**
     * Resolver implementation (SDK-side).
     *
     * @var ResolverContract
     */
    protected ResolverContract $resolver;

    /**
     * VendorBaseModule constructor.
     *
     * @param ResolverContract $resolver Resolver used to locate module files and components
     * @param array<string, mixed> $context Execution context injected by SDK
     */
    public function __construct(ResolverContract $resolver, array $context = [])
    {
        $this->resolver = $resolver;

        // Inject runtime context (portal, moduleName, action, id)
        $this->setContext($context);

        // Load action handlers
        $this->loadModules();
    }

    /* -----------------------------------------------------------------
     |  Execution Pipeline
     | -----------------------------------------------------------------
     */

    /**
     * Execute the resolved action.
     *
     * @return mixed
     */
    public function performAction():mixed
    {
        return $this->handleActions();
    }

    /**
     * Resolve and invoke the appropriate action method.
     *
     * @return mixed
     */
    protected function handleActions()
    {
        $method = $this->getMethodName();

        // 1. Try Main module class
        $mainClass = $this->resolver->resolveModuleMainClass($this->moduleName);

        if (! $mainClass || ! class_exists($mainClass)) {
            abort(404, "Module '{$this->moduleName}' not found.");
        }

        $main = app($mainClass, ['module' => $this]);

        if (method_exists($main, $method)) {
            return $this->invoke($main, $method);
        }

        // 2. Try Action classes
        foreach ($this->modules as $module) {
            if (method_exists($module, $method)) {
                return $this->invoke($module, $method);
            }
        }

        abort(403, "No action defined: {$method}");
    }

    /**
     * Invoke a method with optional ID injection.
     *
     * @param object $target
     * @param string $method
     * @return mixed
     */
    protected function invoke(object $target, string $method)
    {
        $ref = new \ReflectionMethod($target, $method);

        return $ref->getNumberOfParameters() > 0
            ? $target->{$method}($this->id)
            : $target->{$method}();
    }

    /* -----------------------------------------------------------------
     |  Action Loader
     | -----------------------------------------------------------------
     */

    /**
     * Load module action handler classes.
     *
     * @return void
     */
    protected function loadModules(): void
    {
        $this->modules = [];

        $actionPath      = $this->resolver->resolveActionPath($this->moduleName);
        $actionNamespace = $this->resolver->resolveActionNamespace($this->moduleName);

        if (! $actionPath || ! is_dir($actionPath)) {
            return;
        }

        foreach (File::files($actionPath) as $file) {
            $class = $actionNamespace . '\\' . $file->getFilenameWithoutExtension();

            if (class_exists($class)) {
                $this->modules[$class] = new $class($this);
            }
        }
    }

    /* -----------------------------------------------------------------
     |  UI Resolution
     | -----------------------------------------------------------------
     */

    /**
     * Resolve component path for the current action.
     *
     * @return string
     */
    public function componentPath(): string
    {
        return $this->resolver->resolveComponent(
            $this->moduleName,
            $this->action
        );
    }

    /**
     * Resolve referer component path.
     *
     * @return string
     */
    public function refererComponent(): string
    {
        return $this->resolver->resolveComponent(
            $this->moduleName,
            $this->moduleName
        );
    }

    /**
     * Resolve base module component path.
     *
     * @param string $path
     * @return string
     */
    public function moduleComponentPath(string $path = ''): string
    {
        return rtrim(
            $this->resolver->resolveModuleComponentBase($this->moduleName),
            '/'
        ) . '/' . ltrim($path, '/');
    }

    /* -----------------------------------------------------------------
     |  Magic Delegation
     | -----------------------------------------------------------------
     */

    /**
     * Delegate calls to loaded action handlers.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        foreach ($this->modules as $module) {
            if (method_exists($module, $method)) {
                return $module->$method(...$args);
            }
        }

        throw new \BadMethodCallException(
            "Method {$method} not found in module {$this->moduleName}"
        );
    }
}
