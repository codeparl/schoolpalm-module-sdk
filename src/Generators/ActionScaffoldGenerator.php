<?php

namespace SchoolPalm\ModuleSDK\Generators;

use SchoolPalm\ModuleBridge\Services\DtoReflectionService;
use SchoolPalm\ModuleBridge\Manifest\ModuleManifest;

class ActionScaffoldGenerator
{
    protected DtoReflectionService $dtoReflection;

    public function __construct(
        protected string $actionsFile,
        protected array $action,
        protected string $type,
        protected string $dto
    ) {}

    /**
     * @param bool $generateBackend Whether to generate backend action method
     * @param bool $generateUI      Whether to generate frontend Vue page
     */
    public function generate(ModuleManifest $manifest, bool $generateBackend = true, bool $generateUI = true): void
    {
        $this->dtoReflection = new DtoReflectionService($manifest);
        $source = $this->action['source'] ?? 'backend';

        match ($source) {
            'menu'    => $this->generateFrontend($manifest),
            'backend' => $this->generateBackendWithOptionalFrontend($manifest, $generateBackend, $generateUI),
            default   => throw new \RuntimeException("Unknown action source [{$source}]")
        };
    }

    protected function generateBackendWithOptionalFrontend(
        ModuleManifest $manifest,
        bool $generateBackend,
        bool $generateUI
    ): void {
        if ($generateBackend) {
            (new BackendActionGenerator(
                $this->actionsFile,
                $this->action,
                $this->type,
                $this->dto,
                $this->dtoReflection,
                $manifest
            ))->generate();
        }

        // Only generate frontend if type is UI‑suitable and $generateUI is true
        if ($generateUI && in_array($this->type, ['list', 'view', 'create', 'edit'])) {
            $this->generateFrontend($manifest);
        }
    }

    protected function generateFrontend(ModuleManifest $manifest): void
    {
        (new FrontendPageGenerator(
            $this->action,
            $this->type,
            $this->dto,
            $this->dtoReflection,
            $manifest
        ))->generate();
    }
}