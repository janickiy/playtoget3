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
        $this->get('/cp')->assertRedirect('/cp/login');
    }

    public function test_admin_named_routes_use_cp_prefix(): void
    {
        $this->assertSame('/cp/login', route('login', [], false));
        $this->assertSame('/cp', route('admin.dashboard.index', [], false));
        $this->assertSame('/cp/admin', route('admin.admin.index', [], false));
        $this->assertSame('/cp/content/manage-menus', route('admin.menu.index', [], false));
        $this->assertSame('/cp/content/pages', route('admin.pages.index', [], false));
        $this->assertSame('/cp/settings', route('admin.settings.index', [], false));
        $this->assertSame('/cp/datatable/admin', route('admin.datatable.admin', [], false));
        $this->assertSame('/cp/datatable/settings', route('admin.datatable.settings', [], false));
    }
}
