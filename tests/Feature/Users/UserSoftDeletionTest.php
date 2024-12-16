<?php

namespace Tests\Feature\Users;

use App\Models\Comment;
use App\Models\Discussion;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Support\Authentication;
use Tests\TestCase;

class UserSoftDeletionTest extends TestCase
{
    use RefreshDatabase, Authentication;

    private function createUser($email): User
    {
        return User::create([
            'name' => 'User name',
            'surname' => 'User surname',
            'description' => 'User description',
            'email' => 'test@test.com',
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

    private function createTopic(Discussion $discussion, User $user): Topic
    {
        return Topic::create([
            'title' => 'Topic title',
            'content' => 'Topic content',
            'discussion_id' => $discussion->id,
            'author_id' => $user->id
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

    public function test_soft_deleted_user_cannot_authenticate(): void
    {
        $user = User::create([
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => 'password',
        ]);

        $user->delete();

        $this->post('/api/v1/auth/login', [
                'email' => 'test@test.com',
                'password' => 'password',
            ])
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_list_all_users_soft_deleted_are_not_shown(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user1->delete();

        $this->authenticated()
            ->get('/api/v1/users')
            ->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(2)
            ->assertJson(fn (AssertableJson $json) => $json
                ->missing($user1->email)
                ->etc()
            );

        $this->authenticated()
            ->get('/api/v1/users?show_deleted=1')
            ->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(3);
    }

    public function test_show_topics_of_user_soft_deleted_are_shown_with_user_data(): void
    {
        $user = User::factory()->create();
        $discussion = $this->createDiscussion($user);
        $topic = $this->createTopic($discussion, $user);

        $user->delete();

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
                'author' => [
                    'id',
                    'name',
                    'surname',
                    'description',
                    'email',
                    'image',
                    'organization_id',
                    'created_at',
                    'updated_at',
                ]
        ]);
    }

    public function test_show_comments_of_user_soft_deleted_are_shown_with_user_data(): void
    {
        $user = User::factory()->create();
        $discussion = $this->createDiscussion($user);
        $topic = $this->createTopic($discussion, $user);
        $comment = $this->createComment($topic, $user);

        $user->delete();

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
                        'author' => [
                            'id',
                            'name',
                            'surname',
                            'description',
                            'email',
                            'image',
                            'organization_id',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]
            ]);
    }
}