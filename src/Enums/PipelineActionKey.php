<?php

namespace SchoolPalm\ModuleSDK\Enums;

enum PipelineActionKey: string
{
    case VALIDATE_MANIFEST      = 'validate_manifest';
    case VERIFY_DEPENDENCIES    = 'verify_dependencies';
    case GENERATE_STRUCTURE     = 'generate_structure';
    case GENERATE_STUBS         = 'generate_stubs';
    case INSTALL_DEPENDENCIES   = 'install_dependencies';
    case FINALIZE               = 'finalize';

    /**
     * Return all enum values as array (useful for validation)
     */
    public static function values(): array
    {
        return array_map(
            fn (self $case) => $case->value,
            self::cases()
        );
    }
}
