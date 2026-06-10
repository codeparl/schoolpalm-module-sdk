<?php

namespace SchoolPalm\ModuleSDK\Contracts;

use SchoolPalm\ModuleSDK\Pipeline\PipelineAction;

interface SetupPipelineContract{
/**
 * validates the module manifest and returns pipeline actions array
 */
public function validateManifest():array;
/**
 * generates  the module folder structure and returns pipeline actions array
 */
public function generateStructure():array;
/**
 * generates the module stub files and returns pipeline actions array
 */
public function generateStubs():array;
/**
 * finalizes module setup and returns pipeline actions array
 */
public function finalize():array;
/**
 *  return the  pipeline actions object
 */
 public function getPipelineActions():array;

}