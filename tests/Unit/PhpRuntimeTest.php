<?php

namespace Tests\Unit;

use App\Support\PhpRuntime;
use PHPUnit\Framework\TestCase;

class PhpRuntimeTest extends TestCase
{
    public function test_vulnerable_eol_and_untested_runtimes_are_rejected(): void
    {
        foreach (['7.4.33', '8.1.34', '8.2.35', '8.3.36', '8.4.27', '8.5.11', '8.6.0', '8.5.12RC1', '8.5.12beta1', '8.5.13RC1', '', 'invalid'] as $version) {
            $this->assertFalse(PhpRuntime::isSupported($version), $version);
        }
    }

    public function test_patched_releases_and_later_patches_in_tested_branches_are_accepted(): void
    {
        foreach (['8.5.12', '8.5.13', '8.5.20'] as $version) {
            $this->assertTrue(PhpRuntime::isSupported($version), $version);
        }
    }
}
