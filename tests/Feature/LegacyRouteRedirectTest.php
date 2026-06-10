<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyRouteRedirectTest extends TestCase
{
    public function test_home_page_ignores_legacy_task_query(): void
    {
        $this->get('/?task=news')
            ->assertOk()
            ->assertViewIs('front.auth.login');
    }

    public function test_cp_stays_admin_entry_point(): void
    {
        $this->get('/cp')->assertRedirect('/login');
    }
}
