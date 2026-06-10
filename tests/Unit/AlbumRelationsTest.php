<?php

namespace Tests\Unit;

use App\Models\PhotoAlbums;
use App\Models\VideoAlbums;
use Tests\TestCase;

class AlbumRelationsTest extends TestCase
{
    public function test_photo_album_photos_relation_uses_legacy_foreign_key(): void
    {
        $this->assertSame('photoalbum_id', (new PhotoAlbums())->photos()->getForeignKeyName());
    }

    public function test_video_album_videos_relation_uses_legacy_foreign_key(): void
    {
        $this->assertSame('videoalbum_id', (new VideoAlbums())->videos()->getForeignKeyName());
    }
}
