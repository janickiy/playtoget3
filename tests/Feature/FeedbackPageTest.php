<?php

namespace Tests\Feature;

use App\Mail\FeedbackSubmitted;
use App\Models\Feedback;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FeedbackPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('feedback');
        Schema::create('feedback', function (Blueprint $table): void {
            $table->id();
            $table->string('subject')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->dateTime('time')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('feedback');

        parent::tearDown();
    }

    public function test_feedback_page_uses_legacy_form_markup(): void
    {
        $this
            ->get('/feedback')
            ->assertOk()
            ->assertSee('Обратная связь')
            ->assertSee('id="feedback-form"', false)
            ->assertSee('class="form-horizontal"', false)
            ->assertSee('Введите тему сообщения')
            ->assertSee('Укажите ваше имя')
            ->assertSee('Введите Ваш адрес электронной почты')
            ->assertSee('Введите сообщение')
            ->assertSee('btn-form save-button', false);
    }

    public function test_feedback_message_is_saved_and_notification_is_sent(): void
    {
        Mail::fake();

        $response = $this->post('/feedback', [
            'subject' => 'Вопрос по сайту',
            'name' => 'Иван',
            'email' => 'ivan@example.test',
            'message' => 'Текст сообщения',
        ]);

        $response->assertRedirect(route('front.feedback.create'));

        $this->assertDatabaseHas('feedback', [
            'subject' => 'Вопрос по сайту',
            'name' => 'Иван',
            'email' => 'ivan@example.test',
            'message' => 'Текст сообщения',
        ]);

        $this->assertNotNull(Feedback::query()->first()?->time);

        Mail::assertSent(FeedbackSubmitted::class, function (FeedbackSubmitted $mail): bool {
            return $mail->hasTo('ivan@example.test')
                && $mail->feedback->message() === 'Текст сообщения';
        });
    }
}
