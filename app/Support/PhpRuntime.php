<?php

namespace App\Support;

final class PhpRuntime
{
    public static function isSupported(string $version): bool
    {
        // Keep each tested branch's php2shell patch floor explicit.
        return preg_match('/^8\.5\.\d+$/D', $version) === 1
            && version_compare($version, '8.5.12', '>=');
    }
}
