<?php

namespace App\Repositories\Concerns;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Share;

trait DeletesContentRelations
{
    private function deleteContentRelations(string $type, int $contentId): void
    {
        Comment::query()
            ->where('commentable_type', $type)
            ->where('content_id', $contentId)
            ->delete();

        Like::query()
            ->where('likeable_type', $type)
            ->where('content_id', $contentId)
            ->delete();

        Share::query()
            ->where('shareable_type', $type)
            ->where('content_id', $contentId)
            ->delete();
    }
}
