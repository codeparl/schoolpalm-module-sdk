<?php

namespace SchoolPalm\ModuleSDK\Http;

use Illuminate\Http\Request;
use Inertia\Inertia;
use SchoolPalm\ModuleSDK\Core\VendorBaseModule;
use SchoolPalm\ModuleSDK\Resolvers\ModuleResolver;

/**
 * Class ModuleController
 *
 * Handles dynamic routing for vendor modules in the SchoolPalm SDK.
 *
 * Responsibilities:
 * - Detect the current module from the route
 * - Initialize the module’s main class
 * - Perform actions dynamically via VendorBaseModule
 * - Return JSON for AJAX requests or the result for normal requests
 * - Render dashboard component
 */
class ModuleController
{
    /** @var VendorBaseModule|null The currently loaded module instance */
    private ?VendorBaseModule $module = null;

    /** @var string|null Current module name from route */
    private ?string $moduleName;

    /**
     * Constructor
     *
     * Initializes the module based on the route and resolver.
     */
    public function __construct()
    {
        $this->moduleName = VendorBaseModule::normalizeModuleName(
            VendorBaseModule::getRouteSegment('module')
        );

        $resolver = new ModuleResolver([]); // Optionally, inject registry array

        if ($this->moduleName) {
            $moduleClass = $resolver->resolveModuleMainClass($this->moduleName);

            if ($moduleClass && class_exists($moduleClass)) {
                // Instantiate module with resolver
                $this->module = app($moduleClass, ['resolver' => $resolver]);
            }
        }
    }

    /**
     * Handle module routes dynamically
     *
     * @param Request $request
     * @return mixed
     *
     * - Delegates the request to VendorBaseModule->performAction()
     * - Returns JSON if AJAX, otherwise the normal response
     */
    public function handle(Request $request)
    {
        if ($this->module !== null) {
            $result = $this->module->performAction();

            if ($request->ajax() || $request->wantsJson()) {
                return $result;
            }

            return $result;
        }

        abort(404, 'Resource or Module not found');
    }

    /**
     * Render the portal/dashboard component
     *
     * Uses Inertia to render a default dashboard page.
     *
     * @return \Inertia\Response
     */
    public function dashboard()
    {
        return Inertia::render(ModuleResolver::resolveDashboard());
    }
}
