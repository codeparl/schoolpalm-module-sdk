<?php

namespace SchoolPalm\ModuleSDK\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SchoolPalm\ModuleBridge\Support\Helper;

class ActionGenerator
{
    protected array $actions = [];

    public function __construct(
        private string $actionsFile,
        ?array $actions = null,
        ?string $manifestPath = null
    ) {
        

        if ($actions !== null) {
            $this->actions = $this->extractActionNames($actions);
        } elseif ($manifestPath !== null) {
            if (!File::exists($manifestPath)) {
                throw new \RuntimeException('Manifest file not found.');
            }

            $manifest = Helper::loadJson($manifestPath);
            $this->actions = $this->extractActionNames($manifest['actions'] ?? []);
        }
    }

    /* ====================================================
     * MAIN ENTRY
     * ==================================================== */

    public function generate(): void
    {
        if (empty($this->actions)) {
            return;
        }

        $existing = $this->getExistingPhpActions();

        $stubs = $this->buildMissingPhpStubs($this->actions, $existing);

        if (!empty($stubs)) {
            $this->injectPhpStubs($stubs);
        }
    }

    /* ====================================================
     * EXTRACT ACTION NAMES FROM ROUTES
     * ==================================================== */

    protected function extractActionNames(array $actions): array
    {
        return collect($actions)
            ->pluck('route')
            ->filter()
            ->map(function ($route) {

                $route = trim($route, '/');
                $segments = explode('/', $route);

                return strtolower(end($segments));
            })
            ->unique()
            ->values()
            ->toArray();
    }

    /* ====================================================
     * FIND EXISTING GENERATED ACTIONS
     * ==================================================== */

    protected function getExistingPhpActions(): array
    {
        $tokens = token_get_all(File::get($this->actionsFile));
        $existing = [];

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_DOC_COMMENT) {
                if (preg_match('/@module-action\s+([a-z0-9_\-]+)/i', $token[1], $m)) {
                    $existing[] = strtolower($m[1]);
                }
            }
        }

        return array_unique($existing);
    }

    /* ====================================================
     * BUILD MISSING METHOD STUBS
     * ==================================================== */

    protected function buildMissingPhpStubs(array $actions, array $existing): string
    {
        $buffer = '';

        foreach ($actions as $actionName) {

            if (in_array($actionName, $existing, true)) {
                continue;
            }

            $method = 'run' . Str::studly($actionName);

            $buffer .= <<<PHP

    /**
     * @module-action {$actionName}
     * @generated
     */
    public function {$method}()
    {
        // TODO: Implement {$actionName} action

        return response()->json(['message'=>'action {$actionName} created!']);
    }

PHP;
        }
        return $buffer;
    }

    /* ====================================================
     * INJECT INTO CLASS
     * ==================================================== */

    protected function injectPhpStubs(string $stubs): void
    {
        $content = File::get($this->actionsFile);

        $pos = strrpos($content, '}');

        if ($pos === false) {
            throw new \RuntimeException('Invalid PHP class structure.');
        }

        $updated = substr($content, 0, $pos)
            . $stubs
            . "\n}"
        ;

        File::put($this->actionsFile, $updated);
    }
}