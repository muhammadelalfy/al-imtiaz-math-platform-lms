<?php

namespace Tests\Feature;

use Tests\TestCase;

class LaravelReactShellTest extends TestCase
{
    public function test_laravel_serves_the_lms_react_shell_for_web_routes(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertViewIs('app');
    }

    public function test_inertia_requests_resolve_the_lms_component(): void
    {
        $this->get('/', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('component', 'Lms');
    }

    public function test_laravel_emits_https_vite_assets_behind_a_tls_terminating_proxy(): void
    {
        $this->withServerVariables(['HTTP_X_FORWARDED_PROTO' => 'https'])
            ->get('/')
            ->assertOk();

        $this->assertSame('https', $this->app['request']->getScheme());
    }
}
