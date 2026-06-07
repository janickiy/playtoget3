<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyRouteRedirectTest extends TestCase
{
    public function test_legacy_query_routes_redirect_to_new_front_routes(): void
    {
        $cases = [
            '/?task=news' => '/news',
            '/?task=profile&user_id=1' => '/profile/1',
            '/?task=profile&user_id=1&q=messages&sel=2' => '/profile/1/messages/user/2',
            '/?task=profile&user_id=1&q=dialogues' => '/profile/1/messages',
            '/?task=playgrounds' => '/playgrounds',
            '/?task=playgrounds&id_sport_block=17' => '/playgrounds/17',
            '/?task=shops' => '/shops',
            '/?task=shops&id_sport_block=2' => '/shops/2',
            '/?task=calendar' => '/calendar',
            '/?task=fitness' => '/fitness',
            '/?task=fitness&id_sport_block=14' => '/fitness/14',
            '/?task=events' => '/events',
            '/?task=events&event_id=2' => '/events/2',
            '/?task=events&event_id=2&q=members' => '/events/2/members',
            '/?task=events&event_id=2&q=photoalbums' => '/events/2/photoalbums',
            '/?task=events&event_id=2&q=videoalbums' => '/events/2/videoalbums',
            '/?task=events&q=create' => '/events/create',
            '/?task=edit_profile' => '/profile/edit',
            '/?task=friends' => '/friends',
            '/?task=photoalbums' => '/photoalbums',
            '/?task=photoalbums&q=add_photo' => '/photoalbums/add-photo',
            '/?task=photoalbums&q=create_photoalbum' => '/photoalbums/create',
            '/?task=photoalbums&q=edit_photoalbum&id_album=129' => '/photoalbums/edit/129',
            '/?task=photoalbums&id_album=64' => '/photoalbums/64',
            '/?task=photoalbums&user_id=173' => '/photoalbums/user/173',
            '/?task=videoalbums' => '/videoalbums',
            '/?task=videoalbums&q=add_video' => '/videoalbums/add-video',
            '/?task=videoalbums&q=create_videoalbum' => '/videoalbums/create',
            '/?task=videoalbums&q=edit_videoalbum&id_album=19' => '/videoalbums/edit/19',
            '/?task=videoalbums&id_album=19' => '/videoalbums/19',
            '/?task=videoalbums&user_id=173' => '/videoalbums/user/173',
            '/?task=friends&user_id=173' => '/friends/user/173',
            '/?task=teams&user_id=173' => '/teams/user/173',
            '/?task=teams&community_id=3' => '/teams/3',
            '/?task=teams&community_id=3&q=members' => '/teams/3/members',
            '/?task=teams&community_id=3&q=photoalbums' => '/teams/3/photoalbums',
            '/?task=teams&community_id=3&q=photoalbums&q=add_photo' => '/teams/3/photoalbums/add-photo',
            '/?task=teams&community_id=3&q=photoalbums&photo=542' => '/teams/3/photoalbums/photo/542',
            '/?task=teams&community_id=3&q=add_photo' => '/teams/3/photoalbums/add-photo',
            '/?task=teams&community_id=3&q=edit_photoalbum&id_album=14' => '/teams/3/photoalbum/14/edit',
            '/?task=teams&community_id=3&q=videoalbums' => '/teams/3/videoalbums',
            '/?task=teams&community_id=3&q=videoalbums&q=add_video' => '/teams/3/videoalbums/add-video',
            '/?task=teams&community_id=3&q=videoalbums&q=create_videoalbum' => '/teams/3/videoalbums/create',
            '/?task=groups' => '/groups',
            '/?task=groups&q=create' => '/groups/create',
            '/?task=groups&community_id=9' => '/groups/9',
            '/?task=groups&community_id=9&q=members' => '/groups/9/members',
            '/?task=groups&community_id=9&q=edit' => '/groups/9/edit',
            '/?task=ajax_action&action=addmessage' => '/ajax/addmessage',
        ];

        foreach ($cases as $legacy => $modern) {
            $this->get($legacy)->assertRedirect($modern);
        }
    }

    public function test_cp_stays_admin_entry_point(): void
    {
        $this->get('/cp')->assertRedirect('/login');
    }
}
