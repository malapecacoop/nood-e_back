<?php

namespace Tests\Feature\Topics;

use App\Models\Discussion;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Authentication;
use Tests\TestCase;

class TopicCrudAuthTest extends TestCase
{
    use RefreshDatabase, Authentication;

    private function createTopic(Discussion $discussion, User $user): Topic
    {
        return Topic::create([
            'title' => 'Topic title',
            'content' => 'Topic content',
            'discussion_id' => $discussion->id,
            'author_id' => $user->id
        ]);
    }

    private function createDiscussion(User $user): Discussion
    {
        return Discussion::create([
            'title' => 'Discussion title',
            'description' => 'Discussion description',
            'author_id' => $user->id,
        ]);
    }

    public function test_auth_user_can_get_all_discussion_topics(): void
    {        
        $discussion = $this->createDiscussion($this->user);

        $topic = $this->createTopic($discussion, $this->user);

        $this->authenticated()
            ->get("/api/v1/discussions/{$discussion->id}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'author_id',
                'created_at',
                'updated_at',
                'topics' => [
                    '*' => [
                        'id',
                        'title',
                        'content',
                        'discussion_id',
                        'author_id',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    public function test_auth_user_can_get_topic_by_id(): void
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);

        $this->authenticated()
            ->get("/api/v1/discussions/{$discussion->id}/{$topic->id}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'title',
                'content',
                'discussion_id',
                'author_id',
                'created_at',
                'updated_at',
            ]);
    }

    public function test_auth_user_can_create_topic_only_required_fields(): void
    {
        $discussion = $this->createDiscussion($this->user);

        $data = [
            'title' => 'Topic title',
            'content' => 'Topic content',
        ];

        $this->authenticated()
            ->post("/api/v1/discussions/{$discussion->id}", $data)
            ->assertStatus(201)
            ->assertJsonFragment([
                'title' => 'Topic title',
                'content' => 'Topic content',
                'discussion_id' => $discussion->id,
                'author_id' => $this->user->id
            ]);
    }

    public function test_auth_user_can_update_topic_only_required_fields(): void
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);

        $data = [
            'title' => 'Updated topic title',
            'content' => 'Updated topic content',
        ];

        $this->authenticated()
            ->put("/api/v1/discussions/{$discussion->id}/{$topic->id}", $data)
            ->assertStatus(200)
            ->assertJson([
                'title' => 'Updated topic title',
                'content' => 'Updated topic content',
                'discussion_id' => $discussion->id,
                'author_id' => $this->user->id
            ])
            ->assertJsonStructure([
                'title',
                'content',
                'discussion_id',
                'author_id',
                'created_at',
                'updated_at'
            ]);
    }

    public function test_auth_user_cannot_update_topic_not_authored(): void
    {
        $discussion = $this->createDiscussion($this->user);

        $user = User::factory()->create();

        $topic = $this->createTopic($discussion, $user);

        $data = [
            'title' => 'Updated topic title',
            'content' => 'Updated topic content',
        ];

        $this->authenticated()
            ->put("/api/v1/discussions/{$discussion->id}/{$topic->id}", $data)
            ->assertStatus(403);
    }

    public function test_auth_admin_can_update_topic_not_authored(): void
    {
        $discussion = $this->createDiscussion($this->user);

        $user = User::factory()->create();

        $topic = $this->createTopic($discussion, $user);

        $data = [
            'title' => 'Updated topic title',
            'content' => 'Updated topic content',
        ];

        $this->userRoleAdmin()
            ->authenticated()
            ->put("/api/v1/discussions/{$discussion->id}/{$topic->id}", $data)
            ->assertStatus(200)
            ->assertJson([
                'title' => 'Updated topic title',
                'content' => 'Updated topic content',
                'discussion_id' => $discussion->id,
                'author_id' => $user->id
            ]);
    }

    public function test_auth_user_can_delete_topic(): void
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);

        $this->authenticated()
            ->delete("/api/v1/discussions/{$discussion->id}/{$topic->id}")
            ->assertStatus(200)
            ->assertJson(
                ['message' => 'Topic deleted successfully']
            );
    }

    public function test_auth_user_cannot_delete_topic_not_authored(): void
    {
        $discussion = $this->createDiscussion($this->user);

        $user = User::factory()->create();

        $topic = $this->createTopic($discussion, $user);

        $this->authenticated()
            ->delete("/api/v1/discussions/{$discussion->id}/{$topic->id}")
            ->assertStatus(403);
    }

    public function test_auth_admin_can_delete_topic_not_authored(): void
    {
        $discussion = $this->createDiscussion($this->user);

        $user = User::factory()->create();

        $topic = $this->createTopic($discussion, $user);

        $this->userRoleAdmin()
            ->authenticated()
            ->delete("/api/v1/discussions/{$discussion->id}/{$topic->id}")
            ->assertStatus(200)
            ->assertJson(
                ['message' => 'Topic deleted successfully']
            );
    }

    public function test_discussion_cannot_be_deleted_if_it_has_topics(): void
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);

        $this->authenticated()
            ->delete("/api/v1/discussions/{$discussion->id}")
            ->assertStatus(409)
            ->assertJson(['message' => 'Cannot delete discussion with topics']);

        $this->assertDatabaseHas('discussions', [
            'id' => $discussion->id
        ]);
    }

}
