<?php

namespace SchoolPalm\ModuleSDK\Pipeline;

use SchoolPalm\ModuleSDK\Enums\PipelineActionKey;
use SchoolPalm\ModuleSDK\Helpers\Helper;

class PipelineAction
{
    protected string $pipelinePath;
    protected array $pipeline;

    public function __construct(string $pipelinePath)
    {
        $this->pipelinePath = $pipelinePath;
        $this->pipeline = Helper::loadJson($pipelinePath);
    }

    public function get(): array
    {
        return $this->pipeline;
    }

    public function checkStatus(PipelineActionKey $key,string $status):bool
    {
          foreach ($this->pipeline as &$step) {
            if ($step['key'] === $key->value) {
               return $step['status'] == $status;
                break;
            }
        }
        return false;
    }

    public function completed(PipelineActionKey $key):bool
    {
        return $this->checkStatus($key,'completed');
    }

    public function pending(PipelineActionKey $key):bool
    {
        return $this->checkStatus($key,'pending');
    }
    public function running(PipelineActionKey $key):bool
    {
        return $this->checkStatus($key,'running');
    }


    public function failed(PipelineActionKey $key):bool
    {
        return $this->checkStatus($key,'failed');
    }

    public function start(PipelineActionKey $key, ?string $log = null): array
    {
        return $this->update($key, [
            'status' => 'running',
            'log'    => $log ?? '[INFO] Step started...'
        ]);
    }

    public function complete(PipelineActionKey $key, ?string $log = null): array
    {
        return $this->update($key, [
            'status' => 'completed',
            'log'    => $log ?? '[SUCCESS] Step completed successfully'
        ]);
    }

    public function fail(PipelineActionKey $key, string $log): array
    {
        return $this->update($key, [
            'status' => 'failed',
            'log'    => $log
        ]);
    }

    public function appendLog(PipelineActionKey $key, string $log): array
    {
        foreach ($this->pipeline as &$step) {
            if ($step['key'] === $key->value) {
                $step['log'] .= "\n" . $log;
                break;
            }
        }

        return $this->persistAndReturn();
    }

    protected function update(PipelineActionKey $key, array $changes): array
    {
        foreach ($this->pipeline as &$step) {
            if ($step['key'] === $key->value) {
                $step = array_merge($step, $changes);
                break;
            }
        }

        return $this->persistAndReturn();
    }

    protected function persistAndReturn(): array
    {
        Helper::storeJson($this->pipelinePath, $this->pipeline);
        return $this->pipeline;
    }
}
