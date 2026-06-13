<?php

namespace Tests\Feature;

use App\Enums\FeedbackStatus;
use App\Mail\FeedbackStatusChanged;
use App\Models\Admin;
use App\Models\Feedback;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminFeedbackCrudTest extends TestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('admin');
        Schema::enableForeignKeyConstraints();

        Schema::create('admin', function (Blueprint $table): void {
            $table->id();
            $table->string('login')->unique();
            $table->string('password');
            $table->string('name')->nullable();
            $table->string('role');
            $table->rememberToken();
            $table->timestamps();
        });

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

        $this->admin = Admin::query()->create([
            'login' => 'admin',
            'password' => 'password',
            'name' => 'Администратор',
            'role' => Admin::ROLE_ADMIN,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('admin');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_feedback_admin_pages_render(): void
    {
        $feedback = $this->feedback([
            'subject' => 'Вопрос по сайту',
            'message' => 'Помогите с профилем',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.feedback.index'))
            ->assertOk()
            ->assertSee('Обратная связь')
            ->assertSee(route('admin.datatable.feedback'), false);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.feedback.show', ['id' => $feedback->id]))
            ->assertOk()
            ->assertSee('Просмотр обращения')
            ->assertSee('Вопрос по сайту')
            ->assertSee('Помогите с профилем')
            ->assertSee('Новое');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.feedback.edit', ['id' => $feedback->id]))
            ->assertOk()
            ->assertSee('Редактирование обращения')
            ->assertSee('name="answer"', false)
            ->assertSee('name="status"', false);
    }

    public function test_feedback_datatable_returns_actions_and_status_color(): void
    {
        $feedback = $this->feedback([
            'subject' => 'Статус заказа',
            'status' => FeedbackStatus::New->value,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.datatable.feedback', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()
            ->assertJsonPath('data.0.subject', 'Статус заказа')
            ->assertJsonPath('data.0.status', 'Новое')
            ->assertJsonPath('data.0.status_css', 'bg-warning');

        $row = $response->json('data.0');

        $this->assertStringContainsString((string) route('admin.feedback.show', ['id' => $feedback->id]), $row['actions']);
        $this->assertStringContainsString((string) route('admin.feedback.edit', ['id' => $feedback->id]), $row['actions']);
    }

    public function test_feedback_admin_update_changes_status_and_sends_notification(): void
    {
        Mail::fake();

        $feedback = $this->feedback([
            'email' => 'ivan@example.test',
            'status' => FeedbackStatus::New->value,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.feedback.update'), [
                'id' => $feedback->id,
                'status' => FeedbackStatus::Processing->value,
                'answer' => 'Мы уже смотрим обращение.',
            ])
            ->assertRedirect(route('admin.feedback.index'));

        $this->assertDatabaseHas('feedback', [
            'id' => $feedback->id,
            'status' => FeedbackStatus::Processing->value,
            'answer' => 'Мы уже смотрим обращение.',
        ]);

        Mail::assertSent(FeedbackStatusChanged::class, function (FeedbackStatusChanged $mail): bool {
            return $mail->hasTo('ivan@example.test')
                && $mail->previousStatus === FeedbackStatus::New
                && (int) $mail->feedback->status === FeedbackStatus::Processing->value;
        });
    }

    public function test_feedback_admin_requires_answer_when_closing(): void
    {
        Mail::fake();

        $feedback = $this->feedback([
            'status' => FeedbackStatus::Processing->value,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->from(route('admin.feedback.edit', ['id' => $feedback->id]))
            ->put(route('admin.feedback.update'), [
                'id' => $feedback->id,
                'status' => FeedbackStatus::Closed->value,
                'answer' => '',
            ])
            ->assertRedirect(route('admin.feedback.edit', ['id' => $feedback->id]))
            ->assertSessionHasErrors(['answer']);

        $this->assertDatabaseHas('feedback', [
            'id' => $feedback->id,
            'status' => FeedbackStatus::Processing->value,
            'answer' => null,
        ]);

        Mail::assertNothingSent();
    }

    public function test_feedback_admin_closing_sends_answer_notification(): void
    {
        Mail::fake();

        $feedback = $this->feedback([
            'email' => 'ivan@example.test',
            'status' => FeedbackStatus::Processing->value,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.feedback.update'), [
                'id' => $feedback->id,
                'status' => FeedbackStatus::Closed->value,
                'answer' => 'Проблема решена.',
            ])
            ->assertRedirect(route('admin.feedback.index'));

        $this->assertDatabaseHas('feedback', [
            'id' => $feedback->id,
            'status' => FeedbackStatus::Closed->value,
            'answer' => 'Проблема решена.',
        ]);

        Mail::assertSent(FeedbackStatusChanged::class, function (FeedbackStatusChanged $mail): bool {
            return $mail->hasTo('ivan@example.test')
                && $mail->previousStatus === FeedbackStatus::Processing
                && (int) $mail->feedback->status === FeedbackStatus::Closed->value
                && $mail->feedback->answer === 'Проблема решена.';
        });
    }

    /**
     * Создает обращение обратной связи с базовыми полями.
     *
     * @param array<string, mixed> $attributes
     */
    private function feedback(array $attributes = []): Feedback
    {
        /** @var Feedback $feedback */
        $feedback = Feedback::query()->create(array_merge([
            'subject' => 'Тема',
            'name' => 'Иван',
            'email' => 'ivan@example.test',
            'message' => 'Сообщение',
            'status' => FeedbackStatus::New->value,
            'answer' => null,
            'time' => '2026-06-13 10:00:00',
        ], $attributes));

        return $feedback;
    }
}
