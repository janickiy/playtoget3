<?php

namespace Tests\Feature;

use App\Repositories\NewsRepository;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class NewsInfiniteScrollTest extends TestCase
{
    public function test_news_page_contains_infinite_scroll_configuration(): void
    {
        $this->fakeNewsFeed();

        $this->get('/news')
            ->assertStatus(200)
            ->assertSee('id="comment-list"', false)
            ->assertSee('/ajax/get_usernews_list', false)
            ->assertSee('data-news-key="test-news:1"', false)
            ->assertSee('data-number="5"', false)
            ->assertSee('data-offset="5"', false);
    }

    public function test_news_ajax_endpoint_returns_feed_fragment(): void
    {
        $this->fakeNewsFeed();

        $this->getJson('/ajax/get_usernews_list?number=5&offset=0')
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'html',
                'count',
                'has_more',
            ])
            ->assertJson([
                'status' => 1,
            ]);
    }

    public function test_news_ajax_endpoint_stops_on_empty_page(): void
    {
        $this->fakeNewsFeed();

        $this->getJson('/ajax/get_usernews_list?number=5&offset=10')
            ->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'html' => '',
                'count' => 0,
                'has_more' => false,
            ]);
    }

    private function fakeNewsFeed(): void
    {
        $items = collect(range(1, 6))->map(fn (int $id): array => [
            'author_id' => $id,
            'avatar' => '/frontend/images/no-avatar.png',
            'author_name' => 'User ' . $id,
            'online' => false,
            'date' => '05.06.2026',
            'event_key' => 'test-news:' . $id,
            'message' => 'News item ' . $id,
            'likeable_type' => null,
            'content_id' => $id,
            'tells_count' => 0,
            'likes_count' => 0,
        ]);

        $this->mock(NewsRepository::class, function (MockInterface $mock) use ($items): void {
            $mock->shouldReceive('feedPage')
                ->andReturnUsing(
                    fn (int $limit = 5, int $offset = 0): Collection => $items
                        ->slice($offset)
                        ->take($limit)
                        ->values()
                );
        });
    }
}
