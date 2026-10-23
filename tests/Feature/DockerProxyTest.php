<?php

namespace Tests\Feature;

use Tests\TestCase;

class DockerProxyTest extends TestCase
{
    public function test_configured_proxy_preserves_https_redirects(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '172.30.0.1'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'secure.example.test',
                'X-Forwarded-Port' => '443',
            ])->get('http://localhost/dashboard')
            ->assertRedirect('https://secure.example.test/login');
    }

    public function test_untrusted_clients_cannot_override_redirect_scheme_or_host(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'untrusted.example.test',
                'X-Forwarded-Port' => '443',
            ])->get('http://localhost/dashboard')
            ->assertRedirect('http://localhost/login');
    }
}
