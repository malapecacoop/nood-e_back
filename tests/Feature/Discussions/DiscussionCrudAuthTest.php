<?php

namespace Tests\Feature\Discussions;

use App\Models\Discussion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Support\Authentication;
use Tests\TestCase;

class DiscussionCrudAuthTest extends TestCase
{
    use RefreshDatabase, Authentication;

    private function createDiscussion(User $user): Discussion
    {
        return Discussion::create([
            'title' => 'Discussion title',
            'description' => 'Discussion description',
            'author_id' => $user->id,
        ]);
    }

    public function test_not_auth_user_cannot_get_all_discussions(): void
    {
        $this->authenticated('invalid-token')
            ->get('/api/v1/discussions')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_auth_user_can_get_all_discussions(): void
    {
        $this->createDiscussion($this->user);

        $this->authenticated()
            ->get("/api/v1/discussions")
            ->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(1)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'title',
                    'description',
                    'author_id',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function test_auth_user_cannot_list_discussions_not_owned_or_member(): void
    {
        $user = User::factory()->create();

        $discussion = $this->createDiscussion($user);
        $discussion->is_public = false;
        $discussion->save();

        $this->authenticated()
            ->get("/api/v1/discussions")
            ->assertStatus(200)
            ->assertJsonCount(0);

        $discussion->members()->attach($this->user->id);

        $this->authenticated()
            ->get("/api/v1/discussions")
            ->assertStatus(200)
            ->assertJsonCount(1);

        $discussion = $this->createDiscussion($this->user);
        $discussion->is_public = false;
        $discussion->save();

        $this->authenticated()
            ->get("/api/v1/discussions")
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_auth_user_can_get_discussion_by_id(): void
    {        
        $discussion = $this->createDiscussion($this->user);

        $this->authenticated()
            ->get("/api/v1/discussions/{$discussion->id}")
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $discussion->id,
                'title' => 'Discussion title',
                'description' => 'Discussion description',
                'author_id' => $this->user->id,
            ]);
    }

    public function test_auth_user_cannot_get_discussion_not_owned_or_member_by_id(): void
    {
        $user = User::factory()->create();

        $discussion = $this->createDiscussion($user);
        $discussion->is_public = false;
        $discussion->save();

        $this->authenticated()
            ->get("/api/v1/discussions/{$discussion->id}")
            ->assertStatus(404);

        $discussion->members()->attach($this->user->id);

        $this->authenticated()
            ->get("/api/v1/discussions/{$discussion->id}")
            ->assertStatus(200);

        $discussion = $this->createDiscussion($this->user);
        $discussion->is_public = false;
        $discussion->save();

        $this->authenticated()
            ->get("/api/v1/discussions/{$discussion->id}")
            ->assertStatus(200);
    }

    public function test_auth_admin_can_get_discussion_not_owned(): void
    {
        $user = User::factory()->create();
        $discussion = $this->createDiscussion($user);

        $this->userRoleAdmin()
            ->authenticated()
            ->get("/api/v1/discussions/{$discussion->id}")
            ->assertStatus(200);
    }

    public function test_auth_user_can_create_discussion_only_required_fields(): void
    {
        $this->authenticated()
            ->post('/api/v1/discussions', [
                'title' => 'Discussion title',
            ])
            ->assertCreated(201)
            ->assertJsonFragment([
                'title' => 'Discussion title',
                'author_id' => $this->user->id
            ]);
    }

    public function test_auth_user_can_update_discussion_only_required_fields(): void
    {
        $discussion = $this->createDiscussion($this->user);

        $this->authenticated()
            ->put("/api/v1/discussions/{$discussion->id}", [
                'title' => 'Discussion title updated',
            ])
            ->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'Discussion title updated',
                'author_id' => $this->user->id
            ]);
    }

    public function test_auth_user_cannot_update_discussion_not_owned(): void
    {
        $user = User::factory()->create();
        $discussion = $this->createDiscussion($user);

        $this->authenticated()
            ->put("/api/v1/discussions/{$discussion->id}", [
                'title' => 'Discussion title updated',
            ])
            ->assertStatus(403);

        $discussion->members()->attach($this->user->id);

        $this->authenticated()
            ->put("/api/v1/discussions/{$discussion->id}", [
                'title' => 'Discussion title updated',
            ])
            ->assertStatus(403);
    }

    public function test_auth_admin_can_update_discussion_not_owned(): void
    {
        $user = User::factory()->create();
        $discussion = $this->createDiscussion($user);

        $this->userRoleAdmin()
            ->authenticated()
            ->put("/api/v1/discussions/{$discussion->id}", [
                'title' => 'Discussion title updated',
            ])
            ->assertStatus(200);
    }

    public function test_auth_user_can_create_discussion_with_members(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->authenticated()
            ->post('/api/v1/discussions', [
                'title' => 'Discussion title',
                'members' => [$user1->id, $user2->id]
            ])
            ->assertCreated(201)
            ->assertJson([
                'title' => 'Discussion title',
                'author_id' => $this->user->id,
                'members' => [
                    ['id' => $user1->id, 'name' => $user1->name],
                    ['id' => $user2->id, 'name' => $user2->name]
                ]
            ]);
    }

    public function test_auth_user_can_update_discussion_with_members(): void
    {
        $discussion = $this->createDiscussion($this->user);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->authenticated()
            ->put("/api/v1/discussions/{$discussion->id}", [
                'title' => 'Discussion title updated',
                'members' => [$user1->id, $user2->id]
            ])
            ->assertStatus(200)
            ->assertJson([
                'title' => 'Discussion title updated',
                'members' => [
                    ['id' => $user1->id, 'name' => $user1->name],
                    ['id' => $user2->id, 'name' => $user2->name]
                ]
            ]);

        $this->authenticated()
            ->put("/api/v1/discussions/{$discussion->id}", [
                'title' => 'Discussion title updated 2',
                'members' => [$user1->id]
            ])
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json
                ->count('members', 1)
                ->missing($user2->name)
                ->etc()
            );
    }

    public function test_auth_user_can_delete_discussion(): void
    {
        $discussion = $this->createDiscussion($this->user);

        $this->authenticated()
            ->delete("/api/v1/discussions/{$discussion->id}")
            ->assertStatus(200)
            ->assertJson(
                ['message' => 'Discussion deleted successfully']
            );
    }

    public function test_auth_user_cannot_delete_discussion_not_owned(): void
    {
        $user = User::factory()->create();
        $discussion = $this->createDiscussion($user);

        $this->authenticated()
            ->delete("/api/v1/discussions/{$discussion->id}")
            ->assertStatus(403);

        $discussion->members()->attach($this->user->id);

        $this->authenticated()
            ->delete("/api/v1/discussions/{$discussion->id}")
            ->assertStatus(403);
    }

    public function test_auth_admin_can_delete_discussion_not_owned(): void
    {
        $user = User::factory()->create();
        $discussion = $this->createDiscussion($user);

        $this->userRoleAdmin()
            ->authenticated()
            ->delete("/api/v1/discussions/{$discussion->id}")
            ->assertStatus(200);
    }
}
