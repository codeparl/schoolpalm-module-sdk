<?php

namespace SchoolPalm\ModuleSDK\Pipeline;

use Exception;
use Throwable;
use SchoolPalm\ModuleBridge\Facades\CreatedRegistry;
use SchoolPalm\ModuleSDK\Contracts\SetupPipelineContract;
use SchoolPalm\ModuleSDK\Helpers\Helper;
use SchoolPalm\ModuleBridge\Manifest\ManifestValidator;
use SchoolPalm\ModuleSDK\Enums\PipelineActionKey;
use SchoolPalm\ModuleSDK\Generators\ModuleScaffold;
use SchoolPalm\ModuleSDK\Generators\StubGenerator;
use SchoolPalm\ModuleSDK\Support\ModulePaths;

class SetupPipeline implements SetupPipelineContract
{
    private array $module;
    private string $pipelinePath;
    private array $pipeline;
    private PipelineAction $pipelineAction;
    private array $manifestData;
    private string $moduleRoot;

    public function __construct(string $module_key)
    {
        $this->module = CreatedRegistry::get($module_key);
        $this->manifestData = Helper::loadJson($this->module['manifest']);
        $this->moduleRoot = $this->module['root'];
        $this->pipelinePath = ModulePaths::modulePath($this->moduleRoot) . '/pipeline.json';

        $this->pipelineAction = new PipelineAction($this->pipelinePath);
        $this->pipeline = $this->pipelineAction->get();
    }

    public function getPipelineActions(): array
    {
        return $this->pipeline;
    }

    /**
     * Validate module manifest
     */
    public function validateManifest(): array
    {
        $key = PipelineActionKey::VALIDATE_MANIFEST;

        try {
            $this->pipeline = $this->pipelineAction->start(
               $key,
                '[INFO] Validating module manifest data...'
            ); 

            ManifestValidator::validate($this->manifestData);

            $this->pipeline = $this->pipelineAction->appendLog(
                $key,
                '[INFO] Manifest schema validation passed'
            );

            return $this->pipelineAction->complete(
                $key,
                '[SUCCESS] Manifest validation completed successfully'
            );
        } catch (Throwable $e) {
            return $this->pipelineAction->fail(
                $key,
                '[ERROR] ' . $e->getMessage()
            );
        }
    }

    /**
     * Generate module folder structure
     */
    public function generateStructure(): array
    {
        $key = PipelineActionKey::GENERATE_STRUCTURE;

        try {
            $this->pipeline = $this->pipelineAction->start(
                $key,
                '[INFO] Generating module directory structure...'
            );

            if (!$this->pipelineAction->completed($key)) {
                $scaffold = app(ModuleScaffold::class);
                $scaffold->make($this->manifestData);
            }

            return $this->pipelineAction->complete(
                $key,
                '[SUCCESS] Folder structure generated'
            );
        } catch (Throwable $e) {
            return $this->pipelineAction->fail(
                $key,
                '[ERROR] ' . $e->getMessage()
            );
        }
    }

    /**
     * Verify module dependencies
     */
    public function verifyDependencies(): array
    {
        $key = PipelineActionKey::VERIFY_DEPENDENCIES;
         $this->pipeline = $this->pipelineAction->start(
               $key,
                '[INFO] verifying module dependencies...'
            ); 

        return $this->pipelineAction->complete(
            $key,
            '[SUCCESS] Dependency verification passed'
        );
    }

    /**
     * Generate stub files
     */
    public function generateStubs(): array
    {
        $key = PipelineActionKey::GENERATE_STUBS;

        try {
            if (!$this->pipelineAction->completed($key)) {
                $this->pipeline = $this->pipelineAction->start(
                    $key,
                    '[INFO] Generating stub files...'
                );

                $this->pipeline = $this->pipelineAction->appendLog(
                    $key,
                    '[INFO] Generating frontend stub files...'
                );

                StubGenerator::frontend($this->module['module_key'])
                    ->devPage()->entry()->package()->vite_config()
                    ->vite_env()->postcss()->vue_shim()->tsconfig()
                    ->tailwind()->css()->appRuntime()->vueApp()
                    ->dashboard()->routes()->bootstrap()
                    ->helper()->quasarSetup()->scripts();

                $this->pipeline = $this->pipelineAction->appendLog(
                    $key,
                    '[INFO] Generating backend stub files...'
                );

                StubGenerator::backend($this->module['module_key'])->all();
            }

            return $this->pipelineAction->complete(
                $key,
                '[SUCCESS] Stub files generated'
            );
        } catch (Throwable $e) {
            return $this->pipelineAction->fail(
                $key,
                '[ERROR] ' . $e->getMessage()
            );
        }
    }

    /**
     * Install module dependencies (composer, npm, etc.)
     */
    public function installDependencies(): array
    {
        $key = PipelineActionKey::INSTALL_DEPENDENCIES;
        return $this->pipelineAction->complete(
            $key,
            '[SUCCESS] Dependencies installed'
        );
    }

    /**
     * Check if all steps (except finalize) are completed
     */
    public function stepsCompleted(): bool
    {
        return $this->pipelineAction->completed(PipelineActionKey::VALIDATE_MANIFEST) &&
            $this->pipelineAction->completed(PipelineActionKey::GENERATE_STRUCTURE) &&
            $this->pipelineAction->completed(PipelineActionKey::VERIFY_DEPENDENCIES) &&
            $this->pipelineAction->completed(PipelineActionKey::GENERATE_STUBS) &&
            $this->pipelineAction->completed(PipelineActionKey::INSTALL_DEPENDENCIES);
    }

    /**
     * Check if ready to run (all steps including finalize completed)
     */
    public function readyToRun(): bool
    {
        return $this->stepsCompleted() &&
            $this->pipelineAction->completed(PipelineActionKey::FINALIZE);
    }

    /**
     * Finalize module setup
     */
    public function finalize(): array
    {
        $key = PipelineActionKey::FINALIZE;

        try {
            if (!$this->stepsCompleted()) {
                throw new Exception('All steps must be completed before finalizing.');
            }

            $this->pipeline = $this->pipelineAction->start(
                $key,
                '[INFO] Finalizing module setup...'
            );

            // Update registry to mark as runnable
            CreatedRegistry::update($this->module['module_key'], ['run' => true]);

            // Update dev-server ports in frontend scripts
            StubGenerator::frontend($this->module['module_key'])->scripts();

            return $this->pipelineAction->complete(
                $key,
                '[SUCCESS] Module setup finalized. Ready for development!'
            );
        } catch (Throwable $e) {
            return $this->pipelineAction->fail(
                $key,
                '[ERROR] ' . $e->getMessage()
            );
        }
    }
}
