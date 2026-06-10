<?php

namespace App\Repositories;

use App\DTO\Message\MessageData;
use App\Helpers\FrontAssets;
use App\Models\Attachment;
use App\Models\Friend;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MessageRepository extends BaseRepository
{
    private const SENDER_VISIBLE_STATUSES = [0, 1, 3];
    private const RECEIVER_VISIBLE_STATUSES = [0, 1, 2];

    public function __construct(Message $model)
    {
        parent::__construct($model);
    }

    /**
     * @param User $viewer
     * @param User $receiver
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    public function conversation(User $viewer, User $receiver, int $limit = 10, int $offset = 0): Collection
    {
        return $this->visibleBetweenQuery($viewer->id, $receiver->id)
            ->with(['sender', 'attachments.photo.album'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (Message $message): array => $this->serializeMessage($message));
    }

    /**
     * @param User $viewer
     * @param User $receiver
     * @param int $limit
     * @param int $offset
     * @return bool
     */
    public function hasMoreConversation(User $viewer, User $receiver, int $limit = 10, int $offset = 0): bool
    {
        return $this->visibleBetweenQuery($viewer->id, $receiver->id)
            ->offset($offset + $limit)
            ->limit(1)
            ->exists();
    }

    /**
     * @param User $viewer
     * @param int $limit
     * @return Collection
     */
    public function dialogues(User $viewer, int $limit = 100): Collection
    {
        $seenUsers = [];

        return $this->visibleForViewerQuery($viewer->id)
            ->with(['sender', 'receiver', 'attachments.photo.album'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (Message $message) use ($viewer, &$seenUsers): ?array {
                $partner = (int) $message->sender_id === (int) $viewer->id
                    ? $message->receiver
                    : $message->sender;

                if (! $partner || isset($seenUsers[$partner->id])) {
                    return null;
                }

                $seenUsers[$partner->id] = true;

                return [
                    'user' => $partner,
                    'user_id' => (int) $partner->id,
                    'name' => $partner->displayName(),
                    'firstname' => $partner->firstname ?: $partner->displayName(),
                    'lastname' => $partner->firstname ? (string) $partner->lastname : '',
                    'avatar' => FrontAssets::userAvatar($partner),
                    'profile_url' => route('front.profile.show', ['user' => $partner->id]),
                    'message_url' => route('front.profile.messages.show', [
                        'user' => $viewer->id,
                        'recipient' => $partner->id,
                    ]),
                    'last_message' => $this->serializeMessage($message),
                    'unread' => (int) $message->receiver_id === (int) $viewer->id
                        && (int) $message->status === 0,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param User $sender
     * @param User $receiver
     * @param MessageData $data
     * @return Message
     * @throws \Throwable
     */
    public function createMessage(User $sender, User $receiver, MessageData $data): Message
    {
        /** @var Message $message */
        $message = DB::transaction(function () use ($sender, $receiver, $data): Message {
            /** @var Message $created */
            $created = $this->model->newQuery()->create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'content' => $data->content,
                'status' => 0,
            ]);

            foreach ($this->attachmentIds($data->attach) as $photoId) {
                Attachment::query()->create([
                    'type' => 'message',
                    'content_id' => $created->id,
                    'photo_id' => $photoId,
                ]);
            }

            return $created;
        });

        return $message->load(['sender', 'attachments.photo.album']);
    }

    /**
     * @param User $viewer
     * @param int $lastId
     * @param User|null $receiver
     * @param int $limit
     * @return Collection
     */
    public function newMessages(User $viewer, int $lastId = 0, ?User $receiver = null, int $limit = 20): Collection
    {
        $query = $receiver
            ? $this->visibleBetweenQuery($viewer->id, $receiver->id)
            : $this->model->newQuery()
                ->where('receiver_id', $viewer->id)
                ->where('status', 0);

        $messages = $query
            ->with(['sender', 'attachments.photo.album'])
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        if ($receiver) {
            $this->markConversationRead($viewer, $receiver);
        }

        return $messages->map(fn (Message $message): array => $this->serializeMessage($message));
    }

    /**
     * @param User $viewer
     * @param User $sender
     * @return void
     */
    public function markConversationRead(User $viewer, User $sender): void
    {
        $this->model->newQuery()
            ->where('sender_id', $sender->id)
            ->where('receiver_id', $viewer->id)
            ->where('status', 0)
            ->update(['status' => 1]);
    }

    /**
     * @param User $viewer
     * @return int
     */
    public function unreadCount(User $viewer): int
    {
        return $this->model->newQuery()
            ->where('receiver_id', $viewer->id)
            ->where('status', 0)
            ->count();
    }

    /**
     * @param User $sender
     * @param User $receiver
     * @return bool
     */
    public function canSendMessage(User $sender, User $receiver): bool
    {
        if ((int) $sender->id === (int) $receiver->id || $receiver->banned || $receiver->deleted) {
            return false;
        }

        if ($this->hasBlockBetween($sender->id, $receiver->id)) {
            return false;
        }

        $receiver->loadMissing('settings');

        return match ((int) ($receiver->settings?->permission_send_message ?? 0)) {
            1 => $this->isFriend($sender->id, $receiver->id),
            2 => false,
            default => true,
        };
    }

    /**
     * @param User $viewer
     * @param int $messageId
     * @return bool
     */
    public function deleteMessageFor(User $viewer, int $messageId): bool
    {
        /** @var Message|null $message */
        $message = $this->model->newQuery()->whereKey($messageId)->first();

        if (! $message || ! in_array((int) $viewer->id, [(int) $message->sender_id, (int) $message->receiver_id], true)) {
            return false;
        }

        $status = (int) ($message->status ?? 0);
        $newStatus = null;

        if (in_array($status, [0, 1], true)) {
            $newStatus = (int) $message->sender_id === (int) $viewer->id ? 2 : 3;
        } elseif ($status === 2 && (int) $message->receiver_id === (int) $viewer->id) {
            $newStatus = 4;
        } elseif ($status === 3 && (int) $message->sender_id === (int) $viewer->id) {
            $newStatus = 4;
        }

        if ($newStatus === null) {
            return true;
        }

        $message->status = $newStatus;

        return $message->save();
    }

    /**
     * @param User $viewer
     * @param User $partner
     * @return bool
     */
    public function deleteDialogFor(User $viewer, User $partner): bool
    {
        $ids = $this->visibleBetweenQuery($viewer->id, $partner->id)->pluck('id');

        foreach ($ids as $id) {
            $this->deleteMessageFor($viewer, (int) $id);
        }

        return true;
    }

    /**
     * @param Message $message
     * @return array
     */
    public function serializeMessage(Message $message): array
    {
        $sender = $message->sender;
        $content = $this->renderContent((string) $message->content);

        return [
            'id' => (int) $message->id,
            'id_message' => (int) $message->id,
            'sender_id' => (int) $message->sender_id,
            'receiver_id' => (int) $message->receiver_id,
            'avatar' => FrontAssets::userAvatar($sender),
            'firstname' => $sender?->firstname ?: ($sender?->displayName() ?? ''),
            'lastname' => $sender?->firstname ? (string) $sender->lastname : '',
            'created' => $message->created_at?->format('d.m.Y H:i') ?? '',
            'status' => (int) ($message->status ?? 0),
            'content' => $content,
            'image' => $this->renderAttachments($message->attachments),
            'profile_url' => $sender ? route('front.profile.show', ['user' => $sender->id]) : route('front.news.index'),
        ];
    }

    /**
     * @param int $viewerId
     * @param int $otherId
     * @return Builder
     */
    private function visibleBetweenQuery(int $viewerId, int $otherId): Builder
    {
        return $this->model->newQuery()
            ->where(function (Builder $query) use ($viewerId, $otherId): void {
                $query
                    ->where(function (Builder $query) use ($viewerId, $otherId): void {
                        $query
                            ->where('sender_id', $viewerId)
                            ->where('receiver_id', $otherId)
                            ->where(function (Builder $query): void {
                                $query->whereIn('status', self::SENDER_VISIBLE_STATUSES)->orWhereNull('status');
                            });
                    })
                    ->orWhere(function (Builder $query) use ($viewerId, $otherId): void {
                        $query
                            ->where('sender_id', $otherId)
                            ->where('receiver_id', $viewerId)
                            ->where(function (Builder $query): void {
                                $query->whereIn('status', self::RECEIVER_VISIBLE_STATUSES)->orWhereNull('status');
                            });
                    });
            });
    }

    /**
     * @param int $viewerId
     * @return Builder
     */
    private function visibleForViewerQuery(int $viewerId): Builder
    {
        return $this->model->newQuery()
            ->where(function (Builder $query) use ($viewerId): void {
                $query
                    ->where(function (Builder $query) use ($viewerId): void {
                        $query
                            ->where('sender_id', $viewerId)
                            ->where(function (Builder $query): void {
                                $query->whereIn('status', self::SENDER_VISIBLE_STATUSES)->orWhereNull('status');
                            });
                    })
                    ->orWhere(function (Builder $query) use ($viewerId): void {
                        $query
                            ->where('receiver_id', $viewerId)
                            ->where(function (Builder $query): void {
                                $query->whereIn('status', self::RECEIVER_VISIBLE_STATUSES)->orWhereNull('status');
                            });
                    });
            });
    }

    /**
     * @param int $userId
     * @param int $friendId
     * @return bool
     */
    private function hasBlockBetween(int $userId, int $friendId): bool
    {
        return Friend::query()
            ->where('status', 2)
            ->where(function (Builder $query) use ($userId, $friendId): void {
                $query
                    ->where(function (Builder $query) use ($userId, $friendId): void {
                        $query->where('user_id', $userId)->where('friend_id', $friendId);
                    })
                    ->orWhere(function (Builder $query) use ($userId, $friendId): void {
                        $query->where('user_id', $friendId)->where('friend_id', $userId);
                    });
            })
            ->exists();
    }

    /**
     * @param int $userId
     * @param int $friendId
     * @return bool
     */
    private function isFriend(int $userId, int $friendId): bool
    {
        return Friend::query()
            ->where('status', 1)
            ->where(function (Builder $query) use ($userId, $friendId): void {
                $query
                    ->where(function (Builder $query) use ($userId, $friendId): void {
                        $query->where('user_id', $userId)->where('friend_id', $friendId);
                    })
                    ->orWhere(function (Builder $query) use ($userId, $friendId): void {
                        $query->where('user_id', $friendId)->where('friend_id', $userId);
                    });
            })
            ->exists();
    }

    /**
     * @param array|string|null $attach
     * @return array
     */
    private function attachmentIds(array|string|null $attach): array
    {
        if (is_string($attach)) {
            $attach = explode(',', $attach);
        }

        if (! is_array($attach)) {
            return [];
        }

        return collect($attach)
            ->flatMap(fn ($value): array => is_array($value) ? $value : [$value])
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param string $content
     * @return string
     */
    private function renderContent(string $content): string
    {
        $escaped = e($content);
        $linked = preg_replace(
            '~(https?://[^\s<]+)~iu',
            '<a href="$1" target="_blank" rel="noopener">$1</a>',
            $escaped
        );

        return nl2br($linked ?? $escaped, false);
    }

    /**
     * @param Collection $attachments
     * @return string
     */
    private function renderAttachments(Collection $attachments): string
    {
        $items = $attachments
            ->map(function (Attachment $attachment): ?string {
                $url = FrontAssets::photoGallery($attachment->photo);

                if (! $url || ! $attachment->photo) {
                    return null;
                }

                return sprintf(
                    '<li><img border="0" src="%s" class="photo_big" data-num="%d"></li>',
                    e($url),
                    (int) $attachment->photo->id
                );
            })
            ->filter()
            ->implode('');

        return $items !== '' ? '<ul class="attach_image">' . $items . '</ul>' : '';
    }
}
