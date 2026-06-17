<?php

namespace App\Repositories\Concerns;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Share;
use Illuminate\Support\Facades\Schema;

trait DeletesContentRelations
{
    /**
     * Deletes related reactions, nested comments and comment attachments.
     */
    private function deleteContentRelations(string $type, int $contentId): void
    {
        if (Schema::hasTable('comments')) {
            $commentIds = $this->commentTreeIdsForContent($type, $contentId);

            if ($commentIds !== []) {
                if (Schema::hasTable('attachment')) {
                    Attachment::query()
                        ->where('type', 'comment')
                        ->whereIn('content_id', $commentIds)
                        ->delete();
                }

                if (Schema::hasTable('likes')) {
                    Like::query()
                        ->where('likeable_type', 'comment')
                        ->whereIn('content_id', $commentIds)
                        ->delete();
                }

                if (Schema::hasTable('share')) {
                    Share::query()
                        ->where('shareable_type', 'comment')
                        ->whereIn('content_id', $commentIds)
                        ->delete();
                }

                Comment::query()
                    ->whereIn('id', $commentIds)
                    ->delete();
            }
        }

        if (Schema::hasTable('likes')) {
            Like::query()
                ->where('likeable_type', $type)
                ->where('content_id', $contentId)
                ->delete();
        }

        if (Schema::hasTable('share')) {
            Share::query()
                ->where('shareable_type', $type)
                ->where('content_id', $contentId)
                ->delete();
        }
    }

    /**
     * Returns ids root comments and replies for selected content.
     *
     * @return array<int, int>
     */
    private function commentTreeIdsForContent(string $type, int $contentId): array
    {
        $ids = Comment::query()
            ->where('commentable_type', $type)
            ->where('content_id', $contentId)
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        $ids = array_values(array_unique($ids));
        $currentIds = $ids;

        while ($currentIds !== []) {
            $childIds = Comment::query()
                ->whereIn('parent_id', $currentIds)
                ->pluck('id')
                ->map(fn (int|string $id): int => (int) $id)
                ->all();

            $childIds = array_values(array_diff(array_unique($childIds), $ids));

            if ($childIds === []) {
                break;
            }

            $ids = array_merge($ids, $childIds);
            $currentIds = $childIds;
        }

        return array_values(array_unique($ids));
    }
}
