<?php

namespace App\Service;

use App\Models\AcceptedEventMember;
use App\Models\Community;
use App\Models\CommunityRole;
use App\Models\CommunitySetting;
use App\Models\Event;
use App\Models\GeoTarget;
use App\Models\PhotoAlbums;
use App\Models\SportBlock;
use App\Models\VideoAlbums;
use App\Repositories\Concerns\DeletesContentRelations;
use App\Repositories\PhotoalbumRepository;
use App\Repositories\VideoalbumRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContentCascadeDeleteService
{
    use DeletesContentRelations;

    /**
     * Connects repositories and media services used for full entity removal.
     */
    public function __construct(
        private readonly PhotoalbumRepository $photoAlbums,
        private readonly VideoalbumRepository $videoAlbums,
        private readonly CommunityImageService $communityImages,
        private readonly EventCoverService $eventCovers,
        private readonly SportBlockAvatarService $sportBlockAvatars,
    ) {
    }

    /**
     * Deletes community with media, roles, settings, reactions and linked events.
     */
    public function deleteCommunity(Community $community): bool
    {
        return DB::transaction(function () use ($community): bool {
            $type = (string) $community->type;
            $id = (int) $community->id;

            $this->deleteLinkedCommunityEvents($type, $id);
            $this->deleteOwnerPhotoAlbums($type, $id);
            $this->deleteOwnerVideoAlbums($type, $id);
            $this->deleteContentRelations($type, $id);
            $this->deleteCommunityRows($type, $id);
            $this->deleteCommunityImages($community);

            return (bool) $community->delete();
        });
    }

    /**
     * Deletes event with cover, albums, participants, comments, likes and shares.
     */
    public function deleteEvent(Event $event): bool
    {
        return DB::transaction(function () use ($event): bool {
            $id = (int) $event->id;

            $this->deleteOwnerPhotoAlbums('event', $id);
            $this->deleteOwnerVideoAlbums('event', $id);
            $this->deleteContentRelations('event', $id);
            $this->deleteEventRows($id);

            if ($event->cover_page) {
                $this->eventCovers->deleteCover((string) $event->cover_page);
            }

            return (bool) $event->delete();
        });
    }

    /**
     * Deletes sport block with avatar, albums, comments, likes, shares and geo data.
     */
    public function deleteSportBlock(SportBlock $sportBlock): bool
    {
        return DB::transaction(function () use ($sportBlock): bool {
            $type = (string) $sportBlock->type;
            $id = (int) $sportBlock->id;

            $this->deleteOwnerPhotoAlbums($type, $id);
            $this->deleteOwnerVideoAlbums($type, $id);
            $this->deleteContentRelations($type, $id);
            $this->deleteGeoTargets($type, $id);

            if ($sportBlock->avatar) {
                $this->sportBlockAvatars->deleteAvatar((string) $sportBlock->avatar);
            }

            return (bool) $sportBlock->delete();
        });
    }

    /**
     * Deletes events attached to community before removing the community itself.
     */
    private function deleteLinkedCommunityEvents(string $type, int $communityId): void
    {
        if (! Schema::hasTable('accepted_event_members') || ! Schema::hasTable('events')) {
            return;
        }

        $eventIds = AcceptedEventMember::query()
            ->where('eventable_type', $type)
            ->where('member_id', $communityId)
            ->pluck('event_id')
            ->filter()
            ->unique()
            ->values();

        if ($eventIds->isEmpty()) {
            return;
        }

        Event::query()
            ->whereIn('id', $eventIds)
            ->get()
            ->each(fn (Event $event): bool => $this->deleteEvent($event));
    }

    /**
     * Deletes all photo albums belonging to selected owner type and id.
     */
    private function deleteOwnerPhotoAlbums(string $type, int $ownerId): void
    {
        if (! Schema::hasTable('photoalbums')) {
            return;
        }

        $query = PhotoAlbums::query()
            ->where('photoalbumable_type', $type)
            ->where('owner_id', $ownerId);

        if (! Schema::hasTable('photos')) {
            $query->delete();

            return;
        }

        $query->get()->each(fn (PhotoAlbums $album): bool => $this->photoAlbums->deleteAlbum($album));
    }

    /**
     * Deletes all video albums belonging to selected owner type and id.
     */
    private function deleteOwnerVideoAlbums(string $type, int $ownerId): void
    {
        if (! Schema::hasTable('videoalbums')) {
            return;
        }

        $query = VideoAlbums::query()
            ->where('videoalbumable_type', $type)
            ->where('owner_id', $ownerId);

        if (! Schema::hasTable('videos')) {
            $query->delete();

            return;
        }

        $query->get()->each(fn (VideoAlbums $album): bool => $this->videoAlbums->deleteAlbum($album));
    }

    /**
     * Deletes community-specific rows from auxiliary tables.
     */
    private function deleteCommunityRows(string $type, int $communityId): void
    {
        if (Schema::hasTable('community_roles')) {
            CommunityRole::query()
                ->where('community_id', $communityId)
                ->delete();
        }

        if (Schema::hasTable('communities_settings')) {
            CommunitySetting::query()
                ->where('community_id', $communityId)
                ->delete();
        }

        if (Schema::hasTable('accepted_event_members')) {
            AcceptedEventMember::query()
                ->where('eventable_type', $type)
                ->where('member_id', $communityId)
                ->delete();
        }

        $this->deleteGeoTargets($type, $communityId);
    }

    /**
     * Deletes event participants and geo data.
     */
    private function deleteEventRows(int $eventId): void
    {
        if (Schema::hasTable('accepted_event_members')) {
            AcceptedEventMember::query()
                ->where('event_id', $eventId)
                ->delete();
        }

        $this->deleteGeoTargets('event', $eventId);
    }

    /**
     * Deletes geo target rows for selected entity.
     */
    private function deleteGeoTargets(string $type, int $targetId): void
    {
        if (! Schema::hasTable('geo_target')) {
            return;
        }

        GeoTarget::query()
            ->where('target_type', $type)
            ->where('target_id', $targetId)
            ->delete();
    }

    /**
     * Deletes community avatar and cover from actual and legacy directories.
     */
    private function deleteCommunityImages(Community $community): void
    {
        $kinds = match ((string) $community->type) {
            'team' => ['team', 'group'],
            'group' => ['group'],
            default => [(string) $community->type],
        };

        foreach ($kinds as $kind) {
            if ($community->avatar) {
                $this->communityImages->deleteCommunityImage((string) $community->avatar, 'avatar', $kind);
            }

            if ($community->cover_page) {
                $this->communityImages->deleteCommunityImage((string) $community->cover_page, 'cover_page', $kind);
            }
        }
    }
}
