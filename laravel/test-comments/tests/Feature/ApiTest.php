<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use App\Models\VideoPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ВАЖНО:
     * Это заставит RefreshDatabase после migrate:fresh автоматически запускать DatabaseSeeder.
     * Убедись, что DatabaseSeeder создаёт пользователя с id=1.
     */
    protected bool $seed = true;

    private function seededUser(): User
    {
        return User::query()->findOrFail(1);
    }

    private function secondUser(): User
    {
        if (method_exists(User::class, 'factory')) {
            return User::factory()->create();
        }

        return User::query()->create([
            'name' => 'Test User 2',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function it_tests_news_crud_and_comments_flow_with_cursor_pagination(): void
    {
        $user1 = $this->seededUser();
        $user2 = $this->secondUser();

        // ---------- NEWS: store ----------
        $createNews = $this->postJson('/api/news', [
            'title' => 'News 1',
            'description' => 'Desc 1',
        ]);

        $createNews->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'title', 'description', 'created_at', 'updated_at']]);

        $newsId = (int) $createNews->json('data.id');

        // ---------- NEWS: index ----------
        $this->getJson('/api/news?per_page=10')
            ->assertOk()
            ->assertJsonStructure(['data', 'next_cursor', 'prev_cursor', 'per_page']);

        // ---------- NEWS: update ----------
        $this->putJson("/api/news/{$newsId}", [
            'title' => 'News 1 updated',
            'description' => 'Desc updated',
        ])->assertOk()
            ->assertJsonPath('data.title', 'News 1 updated');

        // ---------- COMMENTS: create root comments to news ----------
        // Сделаем 7 корневых, чтобы проверить cursor pagination per_page=3
        for ($i = 1; $i <= 7; $i++) {
            $this->postJson('/api/comments', [
                'user_id' => $user1->id,
                'news_id' => $newsId,
                'body' => "Root comment {$i}",
            ])->assertStatus(201)
                ->assertJsonStructure(['data' => ['id', 'body', 'user_id', 'commentable_type', 'commentable_id', 'parent_id']]);
        }

        // ---------- NEWS: show with cursor pagination for root comments ----------
        $page1 = $this->getJson("/api/news/{$newsId}?per_page=3")
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'title', 'description', 'created_at', 'updated_at'],
                'comments' => ['data', 'next_cursor', 'prev_cursor', 'per_page'],
            ]);

        $items1 = $page1->json('comments.data');
        $this->assertCount(3, $items1);

        $nextCursor1 = $page1->json('comments.next_cursor');
        $this->assertNotEmpty($nextCursor1);

        // page2 via cursor
        $page2 = $this->getJson("/api/news/{$newsId}?per_page=3&cursor={$nextCursor1}")
            ->assertOk();

        $items2 = $page2->json('comments.data');
        $this->assertCount(3, $items2);

        // убедимся, что id не пересекаются между page1 и page2
        $ids1 = array_column($items1, 'id');
        $ids2 = array_column($items2, 'id');
        $this->assertEmpty(array_intersect($ids1, $ids2));

        // ---------- COMMENTS: reply to a comment ----------
        $rootCommentId = (int) $items1[0]['id'];

        $reply = $this->postJson('/api/comments', [
            'user_id' => $user2->id,
            'parent_comment_id' => $rootCommentId,
            'body' => 'Reply 1',
        ])->assertStatus(201);

        $replyId = (int) $reply->json('data.id');

        // ---------- COMMENTS: list replies to comment (parent_id) ----------
        $this->getJson("/api/comments?parent_id={$rootCommentId}&per_page=10")
            ->assertOk()
            ->assertJsonStructure(['data', 'next_cursor', 'prev_cursor', 'per_page'])
            ->assertJsonCount(1, 'data');

        // ---------- COMMENTS: show ----------
        $this->getJson("/api/comments/{$replyId}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'body', 'user_id', 'parent_id']]);

        // ---------- COMMENTS: update (allowed only for same user_id) ----------
        $this->putJson("/api/comments/{$replyId}", [
            'user_id' => $user2->id,
            'body' => 'Reply 1 updated',
        ])->assertOk()
            ->assertJsonPath('data.body', 'Reply 1 updated');

        // ---------- COMMENTS: update (forbidden for чужой user_id) ----------
        $this->putJson("/api/comments/{$replyId}", [
            'user_id' => $user1->id,
            'body' => 'hack attempt',
        ])->assertStatus(403);

        // ---------- COMMENTS: delete (forbidden for чужой user_id) ----------
        $this->deleteJson("/api/comments/{$replyId}", [
            'user_id' => $user1->id,
        ])->assertStatus(403);

        // ---------- COMMENTS: delete (ok) ----------
        $this->deleteJson("/api/comments/{$replyId}", [
            'user_id' => $user2->id,
        ])->assertStatus(204);

        $this->assertDatabaseMissing('comments', ['id' => $replyId]);

        // ---------- NEWS: destroy ----------
        $this->deleteJson("/api/news/{$newsId}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('news', ['id' => $newsId]);
    }

    /** @test */
    public function it_tests_video_posts_crud_and_comments_listing(): void
    {
        $user = $this->seededUser();

        // ---------- VIDEO POSTS: store ----------
        $create = $this->postJson('/api/video-posts', [
            'title' => 'Video 1',
            'description' => 'Video desc',
        ]);

        $create->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'title', 'description', 'created_at', 'updated_at']]);

        $videoId = (int) $create->json('data.id');

        // ---------- VIDEO POSTS: index ----------
        $this->getJson('/api/video-posts?per_page=10')
            ->assertOk()
            ->assertJsonStructure(['data', 'next_cursor', 'prev_cursor', 'per_page']);

        // ---------- COMMENTS: create root comment to video ----------
        $comment = $this->postJson('/api/comments', [
            'user_id' => $user->id,
            'video_post_id' => $videoId,
            'body' => 'Video root comment',
        ])->assertStatus(201);

        $commentId = (int) $comment->json('data.id');

        // ---------- VIDEO POSTS: show with comments ----------
        $this->getJson("/api/video-posts/{$videoId}?per_page=10")
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'title', 'description', 'created_at', 'updated_at'],
                'comments' => ['data', 'next_cursor', 'prev_cursor', 'per_page'],
            ])
            ->assertJsonCount(1, 'comments.data');

        // ---------- COMMENTS: list root comments by video_post_id ----------
        $this->getJson("/api/comments?video_post_id={$videoId}&per_page=10")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // ---------- VIDEO POSTS: update ----------
        $this->putJson("/api/video-posts/{$videoId}", [
            'title' => 'Video 1 updated',
            'description' => 'Video desc updated',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Video 1 updated');

        // ---------- COMMENTS: delete comment ----------
        $this->deleteJson("/api/comments/{$commentId}", [
            'user_id' => $user->id,
        ])->assertStatus(204);

        $this->assertDatabaseMissing('comments', ['id' => $commentId]);

        // ---------- VIDEO POSTS: destroy ----------
        $this->deleteJson("/api/video-posts/{$videoId}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('video_posts', ['id' => $videoId]);
    }

    /** @test */
    public function it_validates_comment_target_must_be_exactly_one(): void
    {
        $user = $this->seededUser();

        // ни одной цели
        $this->postJson('/api/comments', [
            'user_id' => $user->id,
            'body' => 'No target',
        ])->assertStatus(422);

        // две цели одновременно
        $news = News::query()->create(['title' => 'N', 'description' => null]);
        $video = VideoPost::query()->create(['title' => 'V', 'description' => null]);

        $this->postJson('/api/comments', [
            'user_id' => $user->id,
            'news_id' => $news->id,
            'video_post_id' => $video->id,
            'body' => 'Two targets',
        ])->assertStatus(422);
    }
}
