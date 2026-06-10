<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_404_error_page_uses_custom_layout(): void
    {
        $this->get('/missing-page-for-error-template-test')
            ->assertStatus(404)
            ->assertSee('404')
            ->assertSee('Страница не найдена')
            ->assertSee('frontend/images/logo-main.png', false);
    }

    public function test_500_error_page_uses_custom_layout(): void
    {
        $response = response()->view('errors.500', [], 500);
        $content = (string) $response->getContent();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('500', $content);
        $this->assertStringContainsString('Что-то пошло не так', $content);
        $this->assertStringContainsString('frontend/images/logo-main.png', $content);
    }
}
