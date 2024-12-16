<?php

namespace Tests\Feature\Comments;

use App\Models\Comment;
use App\Models\Discussion;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Authentication;
use Tests\TestCase;

class CommentCrudAuthTest extends TestCase
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

    private function createComment(Topic $topic, User $user): Comment
    {
        return Comment::create([
            'content' => 'Comment content',
            'topic_id' => $topic->id,
            'author_id' => $user->id,
        ]);
    }

    public function test_auth_user_can_get_all_topic_comments(): void
    {        
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);
        $comment = $this->createComment($topic, $this->user);

        $this->authenticated()
            ->get("/api/v1/discussions/{$discussion->id}/{$topic->id}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'title',
                'content',
                'author_id',
                'created_at',
                'updated_at',
                'comments' => [
                    '*' => [
                        'id',
                        'content',
                        'topic_id',
                        'author_id',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    public function test_auth_user_can_create_comment_only_required_fields(): void
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);

        $data = [
            'content' => 'Comment content',
        ];

        $this->authenticated()
            ->post("/api/v1/discussions/{$discussion->id}/{$topic->id}", $data)
            ->assertStatus(201)
            ->assertJsonFragment([
                'content' => 'Comment content',
                'author_id' => $this->user->id,
                'topic_id' => $topic->id
            ]);
    }

    public function test_auth_user_can_update_comment_only_required_fields(): void
    {        
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);
        $comment = $this->createComment($topic, $this->user);

        $data = [
            'content' => 'Update comment content',
        ];

        $this->authenticated()
            ->put("/api/v1/discussions/{$discussion->id}/{$topic->id}/{$comment->id}", $data)
            ->assertStatus(200)
            ->assertJsonFragment([
                'content' => 'Update comment content',
                'author_id' => $this->user->id,
                'topic_id' => $topic->id
            ]);
    }

    public function test_auth_user_cannot_update_comment_not_authored(): void
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);

        $user = User::factory()->create();
        $comment = $this->createComment($topic, $user);

        $data = [
            'content' => 'Update comment content',
        ];

        $this->authenticated()
            ->put("/api/v1/discussions/{$discussion->id}/{$topic->id}/{$comment->id}", $data)
            ->assertStatus(403);
    }

    public function test_auth_admin_can_update_comment_not_authored(): void
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);

        $user = User::factory()->create();
        $comment = $this->createComment($topic, $user);

        $data = [
            'content' => 'Update comment content',
        ];

        $this->userRoleAdmin()
            ->authenticated()
            ->put("/api/v1/discussions/{$discussion->id}/{$topic->id}/{$comment->id}", $data)
            ->assertStatus(200)
            ->assertJsonFragment([
                'content' => 'Update comment content',
                'author_id' => $user->id,
                'topic_id' => $topic->id
            ]);
    }

    public function test_auth_user_can_delete_comment(): void
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);
        $comment = $this->createComment($topic, $this->user);

        $this->authenticated()
            ->delete("/api/v1/discussions/{$discussion->id}/{$topic->id}/{$comment->id}")
            ->assertStatus(200)
            ->assertJson(
                ['message' => 'Comment deleted successfully']
            );
    }

    public function test_auth_user_cannot_delete_comment_not_authored()
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);

        $user = User::factory()->create();
        $comment = $this->createComment($topic, $user);

        $this->authenticated()
            ->delete("/api/v1/discussions/{$discussion->id}/{$topic->id}/{$comment->id}")
            ->assertStatus(403);
    }

    public function test_auth_admin_can_delete_comment_not_authored()
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);

        $user = User::factory()->create();
        $comment = $this->createComment($topic, $user);

        $this->userRoleAdmin()
            ->authenticated()
            ->delete("/api/v1/discussions/{$discussion->id}/{$topic->id}/{$comment->id}")
            ->assertStatus(200)
            ->assertJson(
                ['message' => 'Comment deleted successfully']
            );
    }

    public function test_topic_cannot_be_deleted_if_it_has_comments(): void
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);
        $comment = $this->createComment($topic, $this->user);

        $this->authenticated()
            ->delete("/api/v1/discussions/{$discussion->id}/{$topic->id}")
            ->assertStatus(409)
            ->assertJson(['message' => 'Cannot delete topic with comments']);

        $this->assertDatabaseHas('topics', [
            'id' => $topic->id
        ]);
    }

}
