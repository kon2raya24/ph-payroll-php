<?php

declare(strict_types=1);

namespace PhDevUtils\Payroll;

final class DataLoader
{
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /** @return array<string, mixed> */
    public static function load(string $name): array
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        foreach (self::candidatePaths($name) as $path) {
            if (is_file($path)) {
                $json = file_get_contents($path);
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                return self::$cache[$name] = $data;
            }
        }

        throw new \RuntimeException("ph-payroll data file not found: {$name}.json");
    }

    /** @return array<string> */
    private static function candidatePaths(string $name): array
    {
        $file = $name . '.json';
        return [
            __DIR__ . '/../data/' . $file,
            __DIR__ . '/../../../data/' . $file,
        ];
    }
}
