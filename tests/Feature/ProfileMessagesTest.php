<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use App\Repositories\FriendRepository;
use App\Repositories\MessageRepository;
use App\Repositories\UserRepository;
use Mockery\MockInterface;
use Tests\TestCase;

class ProfileMessagesTest extends TestCase
{
    public function test_profile_message_page_renders_legacy_dialog_layout(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $receiver = $this->user(2, 'Дмитрий', 'Панкратов');

        $this->actingAs($viewer, 'web');

        $this->mock(UserRepository::class, function (MockInterface $mock) use ($receiver): void {
            $mock->shouldReceive('findActive')->with(2)->andReturn($receiver);
        });

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($viewer, $receiver): void {
            $mock->shouldReceive('markConversationRead')->once()->with($viewer, $receiver);
            $mock->shouldReceive('canSendMessage')->with($viewer, $receiver)->andReturn(true);
            $mock->shouldReceive('conversation')->with($viewer, $receiver, 10, 0)->andReturn(collect([
                [
                    'id' => 77,
                    'sender_id' => 2,
                    'receiver_id' => 1,
                    'avatar' => 'http://site3.local/uploads/images/user/avatar/2.jpg',
                    'firstname' => 'Дмитрий',
                    'lastname' => 'Панкратов',
                    'created' => '06.06.2026 20:12',
                    'status' => 1,
                    'content' => 'Привет',
                    'image' => '',
                    'profile_url' => 'http://site3.local/profile/2',
                ],
            ]));
            $mock->shouldReceive('hasMoreConversation')->with($viewer, $receiver, 10, 0)->andReturn(false);
        });

        $this->get('/profile/1/messages/user/2')
            ->assertOk()
            ->assertSee('К списку диалогов')
            ->assertSee('class="mess_list"', false)
            ->assertSee('id="message-77"', false)
            ->assertSee('id="addMessageForm"', false)
            ->assertSee('name="receiver_id" value="2"', false)
            ->assertSee('/ajax/getmessages', false)
            ->assertSee('frontend/js/profile.js', false);
    }

    public function test_profile_dialogues_page_renders_dialogues_and_new_dialog_friends(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $friend = $this->user(2, 'Дмитрий', 'Панкратов');

        $this->actingAs($viewer, 'web');

        $this->mock(FriendRepository::class, function (MockInterface $mock) use ($friend): void {
            $mock->shouldReceive('friendsFor')->with(1, 100, 0)->andReturn(collect([$friend]));
        });

        $this->mock(MessageRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('dialogues')->andReturn(collect([
                [
                    'user_id' => 2,
                    'firstname' => 'Дмитрий',
                    'lastname' => 'Панкратов',
                    'avatar' => 'http://site3.local/uploads/images/user/avatar/2.jpg',
                    'profile_url' => 'http://site3.local/profile/2',
                    'message_url' => 'http://site3.local/profile/1/messages/user/2',
                    'unread' => true,
                    'last_message' => [
                        'avatar' => 'http://site3.local/uploads/images/user/avatar/2.jpg',
                        'created' => '06.06.2026 20:12',
                        'content' => 'Новое сообщение',
                    ],
                ],
            ]));
        });

        $this->get('/profile/1/messages')
            ->assertOk()
            ->assertSee('Диалоги')
            ->assertSee('Начать новый диалог')
            ->assertSee('/profile/1/messages/user/2', false)
            ->assertSee('status_red', false)
            ->assertSee('Новое сообщение');
    }

    public function test_add_message_ajax_calls_message_repository(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $receiver = $this->user(2, 'Дмитрий', 'Панкратов');
        $message = new Message(['sender_id' => 1, 'receiver_id' => 2, 'content' => 'Привет', 'status' => 0]);
        $message->id = 55;
        $message->exists = true;

        $this->mock(UserRepository::class, function (MockInterface $mock) use ($receiver): void {
            $mock->shouldReceive('findActive')->with(2)->andReturn($receiver);
        });

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($viewer, $receiver, $message): void {
            $mock->shouldReceive('canSendMessage')->with($viewer, $receiver)->andReturn(true);
            $mock->shouldReceive('createMessage')
                ->once()
                ->withArgs(fn (User $sender, User $recipient, string $content, mixed $attach): bool => $sender === $viewer
                    && $recipient === $receiver
                    && $content === 'Привет'
                    && $attach === [935])
                ->andReturn($message);
            $mock->shouldReceive('serializeMessage')->with($message)->andReturn([
                'id' => 55,
                'id_message' => 55,
                'sender_id' => 1,
                'receiver_id' => 2,
                'content' => 'Привет',
                'image' => '',
            ]);
            $mock->shouldReceive('unreadCount')->with($viewer)->andReturn(0);
        });

        $this->actingAs($viewer, 'web')
            ->postJson('/ajax/addmessage', [
                'receiver_id' => 2,
                'message' => 'Привет',
                'attach' => [935],
            ])
            ->assertOk()
            ->assertJson([
                'status' => 1,
                'id' => 55,
                'content' => 'Привет',
            ]);
    }

    public function test_remove_message_ajax_calls_message_repository(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('deleteMessageFor')
                ->once()
                ->withArgs(fn (User $user, int $messageId): bool => $user === $viewer && $messageId === 55)
                ->andReturn(true);
        });

        $this->actingAs($viewer, 'web')
            ->postJson('/ajax/remove_message', ['id' => 55])
            ->assertOk()
            ->assertJson(['result' => 'success']);
    }

    private function user(int $id, string $firstname, string $lastname): User
    {
        $user = new User([
            'email' => 'user' . $id . '@example.test',
            'password' => 'secret',
            'firstname' => $firstname,
            'lastname' => $lastname,
            'sex' => 'male',
            'confirmed' => true,
            'banned' => false,
            'deleted' => false,
        ]);
        $user->id = $id;
        $user->exists = true;

        return $user;
    }
}
