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
            '/?task=playgrounds&id_sport_block=17' => '/playgrounds/17',
            '/?task=shops&id_sport_block=2' => '/shops/2',
            '/?task=fitness&id_sport_block=14' => '/fitness/14',
            '/?task=events&event_id=2' => '/events/2',
            '/?task=events&event_id=2&q=members' => '/events/2/members',
            '/?task=events&q=create' => '/events/create',
            '/?task=photoalbums&q=add_photo' => '/photoalbums/add-photo',
            '/?task=photoalbums&q=create_photoalbum' => '/photoalbums/create',
            '/?task=teams&community_id=3&q=members' => '/teams/3/members',
            '/?task=teams&community_id=3&q=add_photo' => '/teams/3/photoalbums/add-photo',
            '/?task=teams&community_id=3&q=edit_photoalbum&id_album=14' => '/teams/3/photoalbum/14/edit',
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
