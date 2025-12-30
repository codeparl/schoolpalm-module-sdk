<?php

namespace SchoolPalm\ModuleSDK\Helpers;

use Illuminate\Support\Facades\Config;

class AcademicLevelManager
{
    /**
     * Get all levels from config
     *
     * @return array
     */
    public static function all(): array
    {
        return Config::get('schoolpalm.academic_levels', []);
    }

    /**
     * Get level by number
     *
     * @param int $number
     * @return array|null
     */
    public static function getByNumber(int $number): ?array
    {
        $levels = self::all();
        return $levels[$number] ?? null;
    }

    /**
     * Get number by label or code
     *
     * @param string $level Label or code
     * @return int|null
     */
    public static function getNumber(string $level): ?int
    {
        $level = strtolower($level);
        foreach (self::all() as $number => $data) {
            if (strtolower($data['label']) === $level || strtolower($data['code']) === $level) {
                return $number;
            }
        }
        return null;
    }

    /**
     * Get all labels keyed by number
     *
     * @return array
     */
    public static function allLabels(): array
    {
        return array_map(fn($item) => $item['label'], self::all());
    }

    /**
     * Get all codes keyed by number
     *
     * @return array
     */
    public static function allCodes(): array
    {
        return array_map(fn($item) => $item['code'], self::all());
    }

/**
 * Join an array of level numbers by code in CamelCase for folder names
 * Special case: 0 = common module for all levels
 *
 * @param array $numbers Array of numeric levels, e.g. [1,2,3] or [0]
 * @return string Joined codes in CamelCase, e.g. 'EcePriLse' or 'Common'
 */
public static function joinByCodes(array $numbers): string
{
    // Handle special "common" case
    if (empty($numbers) || in_array(0, $numbers)) {
        return 'Common';
    }

    $codes = [];

    foreach ($numbers as $number) {
        $level = self::getByNumber($number);
        if ($level) {
            $codes[] = ucfirst(strtolower($level['code']));
        }
    }

    return implode('', $codes);
}

}
