<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    private function authedUser(): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $org, $token];
    }

    public function test_can_list_tags(): void
    {
        [$user, $org, $token] = $this->authedUser();
        Tag::factory()->count(3)->create(['organization_id' => $org->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/tags');

        $response->assertOk();
        $this->assertCount(3, $response->json());
    }

    public function test_can_create_tag(): void
    {
        [$user, $org, $token] = $this->authedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/tags', [
                'name' => 'bug',
                'color' => 'red',
            ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'bug')
            ->assertJsonPath('color', 'red');
    }

    public function test_tag_tenant_isolation(): void
    {
        [$user1, $org1, $token1] = $this->authedUser();
        [$user2, $org2, $token2] = $this->authedUser();

        Tag::factory()->count(3)->create(['organization_id' => $org1->id]);
        Tag::factory()->count(2)->create(['organization_id' => $org2->id]);

        // User from org1 sees only 3 tags
        $response = $this->withHeader('Authorization', "Bearer $token1")
            ->getJson('/api/tags');

        $response->assertOk();
        $this->assertCount(3, $response->json());
    }

    public function test_can_attach_tags_to_ticket(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $tags = Tag::factory()->count(2)->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/tickets/{$ticket->id}", [
                'tag_ids' => $tags->pluck('id')->toArray(),
            ]);

        $response->assertOk();
        $this->assertCount(2, $response->json('tags'));
    }

    public function test_can_filter_tickets_by_tag(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $tag = Tag::factory()->create(['organization_id' => $org->id, 'name' => 'urgent-bug']);

        $ticketWithTag = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);
        $ticketWithTag->tags()->attach($tag->id);

        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/tickets?tag=urgent-bug');

        $response->assertOk();
        $this->assertEquals(1, $response->json('total'));
    }

    public function test_can_delete_tag(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $tag = Tag::factory()->create(['organization_id' => $org->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/tags/{$tag->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
