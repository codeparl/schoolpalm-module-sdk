<?php

namespace SchoolPalm\ModuleSDK\Http;

use Illuminate\Http\Request;
use Inertia\Inertia;
use SchoolPalm\ModuleBridge\Context\CurrentContext;
use SchoolPalm\ModuleSDK\Core\BaseModule;
use SchoolPalm\ModuleSDK\Core\ModuleRegistry;
use SchoolPalm\ModuleSDK\Helpers\Helper;
use SchoolPalm\ModuleSDK\Resolvers\ModuleResolver;

/**
 * Class ModuleController
 *
 * Handles dynamic routing for vendor modules in the SchoolPalm SDK.
 *
 * Responsibilities:
 * - Detect the current module from the route
 * - Initialize the module’s main class
 * - Perform actions dynamically via BaseModule
 * - Return JSON for AJAX requests or the result for normal requests
 * - Render dashboard component
 */
class ModuleController
{
    /** @var BaseModule|null The currently loaded module instance */
    private ?BaseModule $module = null;

    /** @var string|null Current module name from route */
    private ?string $moduleName = null;

    /**
     * ModuleController constructor.
     *
     * Only stores the module name; actual module resolution is lazy.
     */
    public function __construct()
    {
        $this->moduleName = Helper::normalizeModuleName(
            Helper::getRouteSegment('module')
        );
    }

    /**
     * Lazy-load and initialize the module
     *
     * @return BaseModule|null
     */
    private function resolveModule(): ?BaseModule
    {
        if ($this->module !== null) {
            return $this->module;
        }

        if (! $this->moduleName) {
            // No module requested; return null to fallback to dashboard
            return null;
        }


        $context = [
            'portal'     => Helper::getRouteSegment('portal'),
            'moduleName' => $this->moduleName,
            'action'     => Helper::getRouteSegment('action'),
            'id'         => Helper::getRouteSegment('id'),
        ];

        $baseModule = new BaseModule($context);

        // Inject resolver
        $resolver = new ModuleResolver(app(ModuleRegistry::class)->all());
        $baseModule->setResolver($resolver);

        // Set module to baseModule; child module will handle its own execution
        $this->module = $baseModule;

        return $this->module;
    }

    /**
     * Handle dynamic module routes
     *
     * @param Request $request
     * @return mixed
     */
    public function handle(Request $request)
    {
        $module = $this->resolveModule();


        if ($module === null) {
            // No module requested; just render dashboard
            return $this->dashboard();
        }


        $result = $module->performAction();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        return $result;
    }

    /**
     * Render the portal/dashboard component
     *
     * @return \Inertia\Response
     */
    public function dashboard()
    {
        $module = $this->resolveModule();

        // Even if module is null, create a temporary BaseModule with resolver for dashboard
        if ($module === null) {
            $module = new BaseModule(['portal' => Helper::getRouteSegment('portal')]);
            $module->setResolver(new ModuleResolver(app(ModuleRegistry::class)->all()));
        }

        return Inertia::render(
            $module->getResolver()->resolveDashboard()
        );
    }
}
