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
            $table->tinyInteger('status')->default(0);
            $table->text('answer')->nullable();
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
            ->assertSee('Проверочный код')
            ->assertSee('id="feedback-captcha"', false)
            ->assertSee(route('front.feedback.captcha'), false)
            ->assertSee('btn-form save-button', false);
    }

    public function test_feedback_message_is_saved_and_notification_is_sent(): void
    {
        Mail::fake();

        $response = $this->withSession(['feedback_captcha_code' => 'ABCDE'])->post('/feedback', [
            'subject' => 'Вопрос по сайту',
            'name' => 'Иван',
            'email' => 'ivan@example.test',
            'message' => 'Текст сообщения',
            'captcha' => 'abcde',
        ]);

        $response->assertRedirect(route('front.feedback.create'));

        $this->assertDatabaseHas('feedback', [
            'subject' => 'Вопрос по сайту',
            'name' => 'Иван',
            'email' => 'ivan@example.test',
            'message' => 'Текст сообщения',
            'status' => 0,
            'answer' => null,
        ]);

        $this->assertNotNull(Feedback::query()->first()?->time);

        Mail::assertSent(FeedbackSubmitted::class, function (FeedbackSubmitted $mail): bool {
            return $mail->hasTo('ivan@example.test')
                && $mail->feedback->message() === 'Текст сообщения';
        });
    }

    public function test_feedback_form_requires_all_fields_and_captcha(): void
    {
        Mail::fake();

        $this
            ->from('/feedback')
            ->post('/feedback', [
                'subject' => '',
                'name' => '',
                'email' => '',
                'message' => '',
                'captcha' => '',
            ])
            ->assertRedirect('/feedback')
            ->assertSessionHasErrors(['subject', 'name', 'email', 'message', 'captcha']);

        $this->assertDatabaseCount('feedback', 0);
        Mail::assertNothingSent();
    }

    public function test_feedback_message_is_not_saved_with_invalid_captcha(): void
    {
        Mail::fake();

        $this
            ->withSession(['feedback_captcha_code' => 'ABCDE'])
            ->from('/feedback')
            ->post('/feedback', [
                'subject' => 'Вопрос по сайту',
                'name' => 'Иван',
                'email' => 'ivan@example.test',
                'message' => 'Текст сообщения',
                'captcha' => 'WRONG',
            ])
            ->assertRedirect('/feedback')
            ->assertSessionHasErrors(['captcha']);

        $this->assertDatabaseCount('feedback', 0);
        Mail::assertNothingSent();
    }

    public function test_feedback_captcha_image_sets_session_code(): void
    {
        $this
            ->get('/feedback/captcha')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertSessionHas('feedback_captcha_code');
    }
}
