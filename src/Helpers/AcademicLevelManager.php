<?php

namespace SchoolPalm\ModuleSDK\Helpers;

use SchoolPalm\ModuleSDK\ModuleSDKServiceProvider;

final class AcademicLevelManager
{
    /**
     * Get all academic levels
     *
     * @return array<int, array{label:string, code:string}>
     */
    public static function all(): array
    {
        return ModuleSDKServiceProvider::$academicLevels ?? [];
    }

    /**
     * Get a level by its numeric key
     */
    public static function getByNumber(int $number): ?array
    {
        return self::all()[$number] ?? null;
    }

    /**
     * Get level number by label or code (case-insensitive)
     */
    public static function getNumber(string $value): ?int
    {
        $value = strtolower(trim($value));

        foreach (self::all() as $number => $data) {
            if (
                strtolower($data['label']) === $value ||
                strtolower($data['code']) === $value
            ) {
                return $number;
            }
        }

        return null;
    }

    /**
     * Get all labels keyed by number
     */
    public static function allLabels(): array
    {
        $out = [];

        foreach (self::all() as $number => $data) {
            $out[$number] = $data['label'];
        }

        return $out;
    }

    /**
     * Get all codes keyed by number
     */
    public static function allCodes(): array
    {
        $out = [];

        foreach (self::all() as $number => $data) {
            $out[$number] = $data['code'];
        }

        return $out;
    }

    /**
     * Join level numbers by codes in CamelCase
     * Used for module folder names.
     *
     * Special cases:
     * - [] or [0] → "Common"
     *
     * @example [1,2,3] → "EcePriLse"
     */
    public static function joinByCodes(array $numbers): string
    {
        if (empty($numbers) || in_array(0, $numbers, true)) {
            return 'Common';
        }

        $codes = [];

        foreach ($numbers as $number) {
            $level = self::getByNumber((int) $number);

            if ($level && isset($level['code'])) {
                $codes[] = ucfirst(strtolower($level['code']));
            }
        }

        return $codes ? implode('', $codes) : 'Common';
    }


    /**
 * Check if a level exists and belongs to a module folder
 *
 * @param string $level Level number, code, or label
 * @param string $folderName Module folder name (e.g. EcePri, Common)
 * @return bool
 */
public static function levelExists(string $level, string $folderName): bool
{
    $folderName = trim($folderName);

    // Common module accepts all levels
    if (strcasecmp($folderName, 'Common') === 0) {
        return true;
    }

    // Resolve numeric level
    if (is_numeric($level)) {
        $number = (int) $level;
    } else {
        $number = self::getNumber($level);
    }

    if (!$number) {
        return false;
    }

    $levelData = self::getByNumber($number);
    if (!$levelData || empty($levelData['code'])) {
        return false;
    }

    // Normalize folder name and code
    $folderName = strtolower($folderName);
    $code = ucfirst(strtolower($levelData['code']));

    // Check if folder contains the level code
    return str_contains($folderName, strtolower($code));
}

}