<?php

namespace Tests\Feature;

use App\Models\AcceptedEventMember;
use App\Models\Comment;
use App\Models\Community;
use App\Models\CommunityRole;
use App\Models\CommunitySetting;
use App\Models\Event;
use App\Models\GeoTarget;
use App\Models\Like;
use App\Models\Photo;
use App\Models\PhotoAlbums;
use App\Models\Share;
use App\Models\SportBlock;
use App\Models\Video;
use App\Models\VideoAlbums;
use App\Models\VideoView;
use App\Service\ContentCascadeDeleteService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentCascadeDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();

        foreach (array_reverse($this->tables()) as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        $this->createTables();

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (array_reverse($this->tables()) as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_community_delete_removes_related_content_media_and_linked_events(): void
    {
        $community = Community::query()->create([
            'type' => 'group',
            'name' => 'Cascade group',
            'avatar' => 'group-avatar.jpg',
            'cover_page' => 'group-cover.jpg',
            'status' => 1,
        ]);

        Storage::disk('public')->put('images/groupcontent/avatar/group-avatar.jpg', 'avatar');
        Storage::disk('public')->put('images/groupcontent/cover_page/group-cover.jpg', 'cover');

        CommunityRole::query()->create(['community_id' => $community->id, 'user_id' => 10, 'role' => 1]);
        CommunitySetting::query()->create(['community_id' => $community->id]);
        GeoTarget::query()->create(['target_type' => 'group', 'target_id' => $community->id]);

        $communityPhoto = $this->photoFor('group', (int) $community->id, 'group-photo.jpg');
        $communityVideo = $this->videoFor('group', (int) $community->id);
        $this->contentReactions('group', (int) $community->id);
        $this->contentReactions('photo', (int) $communityPhoto->id);
        $this->contentReactions('video', (int) $communityVideo->id);

        $event = Event::query()->create([
            'name' => 'Linked event',
            'cover_page' => 'event-cover.jpg',
            'status' => 1,
        ]);

        Storage::disk('public')->put('images/events/cover_page/event-cover.jpg', 'cover');

        AcceptedEventMember::query()->create([
            'event_id' => $event->id,
            'eventable_type' => 'group',
            'member_id' => $community->id,
            'role' => 3,
        ]);
        GeoTarget::query()->create(['target_type' => 'event', 'target_id' => $event->id]);
        $this->photoFor('event', (int) $event->id, 'event-photo.jpg');
        $this->videoFor('event', (int) $event->id);
        $this->contentReactions('event', (int) $event->id);

        app(ContentCascadeDeleteService::class)->deleteCommunity($community);

        $this->assertDatabaseMissing('communities', ['id' => $community->id]);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        $this->assertSame(0, PhotoAlbums::query()->count());
        $this->assertSame(0, Photo::query()->count());
        $this->assertSame(0, VideoAlbums::query()->count());
        $this->assertSame(0, Video::query()->count());
        $this->assertSame(0, Comment::query()->count());
        $this->assertSame(0, Like::query()->count());
        $this->assertSame(0, Share::query()->count());
        $this->assertSame(0, AcceptedEventMember::query()->count());
        $this->assertSame(0, CommunityRole::query()->count());
        $this->assertSame(0, CommunitySetting::query()->count());
        $this->assertSame(0, GeoTarget::query()->count());
        Storage::disk('public')->assertMissing('images/groupcontent/avatar/group-avatar.jpg');
        Storage::disk('public')->assertMissing('images/groupcontent/cover_page/group-cover.jpg');
        Storage::disk('public')->assertMissing('images/events/cover_page/event-cover.jpg');
        Storage::disk('public')->assertMissing('images/photogallery/group/group-photo.jpg');
        Storage::disk('public')->assertMissing('images/photogallery/event/event-photo.jpg');
    }

    public function test_sport_block_delete_removes_related_content_and_avatar(): void
    {
        $sportBlock = SportBlock::query()->create([
            'type' => 'shop',
            'name' => 'Cascade shop',
            'avatar' => 'shop-avatar.jpg',
            'status' => 1,
        ]);

        Storage::disk('public')->put('images/sportblocks/avatar/shop-avatar.jpg', 'avatar');
        $photo = $this->photoFor('shop', (int) $sportBlock->id, 'shop-photo.jpg');
        $video = $this->videoFor('shop', (int) $sportBlock->id);
        $this->contentReactions('shop', (int) $sportBlock->id);
        $this->contentReactions('photo', (int) $photo->id);
        $this->contentReactions('video', (int) $video->id);
        GeoTarget::query()->create(['target_type' => 'shop', 'target_id' => $sportBlock->id]);

        app(ContentCascadeDeleteService::class)->deleteSportBlock($sportBlock);

        $this->assertDatabaseMissing('sport_blocks', ['id' => $sportBlock->id]);
        $this->assertSame(0, PhotoAlbums::query()->count());
        $this->assertSame(0, Photo::query()->count());
        $this->assertSame(0, VideoAlbums::query()->count());
        $this->assertSame(0, Video::query()->count());
        $this->assertSame(0, Comment::query()->count());
        $this->assertSame(0, Like::query()->count());
        $this->assertSame(0, Share::query()->count());
        $this->assertSame(0, GeoTarget::query()->count());
        Storage::disk('public')->assertMissing('images/sportblocks/avatar/shop-avatar.jpg');
        Storage::disk('public')->assertMissing('images/photogallery/shop/shop-photo.jpg');
    }

    private function photoFor(string $type, int $ownerId, string $filename): Photo
    {
        $album = PhotoAlbums::query()->create([
            'name' => $type . ' album',
            'photoalbumable_type' => $type,
            'owner_id' => $ownerId,
        ]);

        Storage::disk('public')->put('images/photogallery/' . $type . '/' . $filename, 'photo');
        Storage::disk('public')->put('images/photogallery/' . $type . '/s_' . $filename, 'thumb');

        return Photo::query()->create([
            'photoalbum_id' => $album->id,
            'photo' => $filename,
            'small_photo' => 's_' . $filename,
            'owner_id' => 1,
        ]);
    }

    private function videoFor(string $type, int $ownerId): Video
    {
        $album = VideoAlbums::query()->create([
            'name' => $type . ' album',
            'videoalbumable_type' => $type,
            'owner_id' => $ownerId,
        ]);

        $video = Video::query()->create([
            'videoalbum_id' => $album->id,
            'provider' => 'youtube',
            'video' => $type . '-video',
            'owner_id' => 1,
        ]);

        VideoView::query()->create(['video_id' => $video->id, 'user_id' => 1]);

        return $video;
    }

    private function contentReactions(string $type, int $contentId): void
    {
        $comment = Comment::query()->create([
            'commentable_type' => $type,
            'content_id' => $contentId,
            'user_id' => 1,
            'behalfable_type' => 'user',
            'behalf_id' => 1,
            'content' => 'Comment',
            'parent_id' => 0,
        ]);

        $reply = Comment::query()->create([
            'commentable_type' => $type,
            'content_id' => $contentId,
            'user_id' => 1,
            'behalfable_type' => 'user',
            'behalf_id' => 1,
            'content' => 'Reply',
            'parent_id' => $comment->id,
        ]);

        foreach ([$comment->id, $reply->id] as $commentId) {
            Like::query()->create(['user_id' => 1, 'likeable_type' => 'comment', 'content_id' => $commentId]);
            Share::query()->create(['user_id' => 1, 'shareable_type' => 'comment', 'content_id' => $commentId]);
        }

        Like::query()->create(['user_id' => 1, 'likeable_type' => $type, 'content_id' => $contentId]);
        Share::query()->create(['user_id' => 1, 'shareable_type' => $type, 'content_id' => $contentId]);
    }

    /**
     * @return array<int, string>
     */
    private function tables(): array
    {
        return [
            'communities',
            'events',
            'sport_blocks',
            'accepted_event_members',
            'photoalbums',
            'photos',
            'videoalbums',
            'videos',
            'video_views',
            'comments',
            'likes',
            'share',
            'attachment',
            'community_roles',
            'communities_settings',
            'geo_target',
        ];
    }

    private function createTables(): void
    {
        Schema::create('communities', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->string('cover_page')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('cover_page')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        Schema::create('sport_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        Schema::create('accepted_event_members', function (Blueprint $table): void {
            $table->id();
            $table->string('eventable_type')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->tinyInteger('role')->nullable();
            $table->unsignedBigInteger('event_id')->nullable();
        });

        Schema::create('photoalbums', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('photoalbumable_type')->nullable();
            $table->unsignedBigInteger('owner_id');
            $table->timestamps();
        });

        Schema::create('photos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('photoalbum_id')->nullable();
            $table->string('small_photo')->nullable();
            $table->string('photo')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->tinyInteger('banned')->default(0);
            $table->tinyInteger('moderate')->default(0);
            $table->timestamps();
        });

        Schema::create('videoalbums', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('videoalbumable_type')->nullable();
            $table->unsignedBigInteger('owner_id');
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('videoalbum_id')->nullable();
            $table->string('provider')->nullable();
            $table->string('video')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->tinyInteger('banned')->default(0);
            $table->timestamps();
        });

        Schema::create('video_views', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('video_id')->nullable();
        });

        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->string('commentable_type')->nullable();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('behalfable_type')->nullable();
            $table->unsignedBigInteger('behalf_id')->nullable();
            $table->text('content')->nullable();
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->timestamps();
        });

        Schema::create('likes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('likeable_type')->nullable();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->dateTime('time')->nullable();
        });

        Schema::create('share', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('shareable_type')->nullable();
            $table->dateTime('time')->nullable();
            $table->unsignedBigInteger('content_id')->nullable();
        });

        Schema::create('attachment', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('content_id');
            $table->unsignedBigInteger('photo_id');
        });

        Schema::create('community_roles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('community_id')->nullable();
            $table->tinyInteger('role')->nullable();
        });

        Schema::create('communities_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('community_id')->nullable();
        });

        Schema::create('geo_target', function (Blueprint $table): void {
            $table->id();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
        });
    }
}
