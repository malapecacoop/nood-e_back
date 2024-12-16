<?php

namespace Tests\Feature\Comments;

use App\Models\Comment;
use App\Models\Discussion;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Authentication;
use Tests\TestCase;

class CommentObserverTest extends TestCase
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

    public function test_topic_is_updated_when_comment_is_created()
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);

        $this->assertNull($topic->last_update);

        $this->createComment($topic, $this->user);
        
        $topic->refresh();

        $this->assertNotNull($topic->last_update);
        $this->assertEquals(now(), $topic->last_update);
    }

    public function test_comment_creation_increments_comments_count()
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);

        $this->assertEquals(0, $topic->comments_counter);

        $this->createComment($topic, $this->user);
        
        $topic->refresh();

        $this->assertEquals(1, $topic->comments_counter);
    }

    public function test_comment_deletion_decrements_comments_count()
    {
        $discussion = $this->createDiscussion($this->user);
        $topic = $this->createTopic($discussion, $this->user);

        $this->assertNull($topic->last_update);
        $this->assertEquals(0, $topic->comments_counter);

        $comment = $this->createComment($topic, $this->user);
        
        $topic->refresh();

        $this->assertEquals(1, $topic->comments_counter);

        $comment->delete();

        $topic->refresh();
        $this->assertEquals(null, $topic->comments_counter);
    }
}
