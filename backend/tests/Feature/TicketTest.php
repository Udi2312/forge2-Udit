<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private function authedUser(): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $token = $user->createToken('test')->plainTextToken;

        return [$user, $org, $token];
    }

    public function test_can_list_tickets(): void
    {
        [$user, $org, $token] = $this->authedUser();

        Ticket::factory()->count(3)->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/tickets');

        $response->assertOk();
        $this->assertEquals(3, $response->json('total'));
    }

    public function test_can_create_ticket(): void
    {
        [$user, $org, $token] = $this->authedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/tickets', [
                'subject' => 'Cannot login',
                'description' => 'I am unable to access my account',
                'priority' => 'high',
            ]);

        $response->assertCreated()
            ->assertJsonPath('subject', 'Cannot login')
            ->assertJsonPath('status', 'open');
    }

    public function test_can_show_ticket(): void
    {
        [$user, $org, $token] = $this->authedUser();

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/tickets/{$ticket->id}");

        $response->assertOk()
            ->assertJsonPath('id', $ticket->id);
    }

    public function test_can_update_ticket(): void
    {
        [$user, $org, $token] = $this->authedUser();

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/tickets/{$ticket->id}", [
                'status' => 'resolved',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'resolved');
    }

    public function test_ticket_tenant_isolation(): void
    {
        [$user1, $org1, $token1] = $this->authedUser();
        [$user2, $org2, $token2] = $this->authedUser();

        $ticket1 = Ticket::factory()->create([
            'organization_id' => $org1->id,
            'requester_id' => $user1->id,
        ]);

        Ticket::factory()->create([
            'organization_id' => $org2->id,
            'requester_id' => $user2->id,
        ]);

        // User from org1 should only see org1 tickets
        $response = $this->withHeader('Authorization', "Bearer $token1")
            ->getJson('/api/tickets');

        $response->assertOk();
        $this->assertEquals(1, $response->json('total'));
    }

    public function test_can_delete_ticket(): void
    {
        [$user, $org, $token] = $this->authedUser();

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertOk();
        $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
    }

    public function test_can_add_comment_to_ticket(): void
    {
        [$user, $org, $token] = $this->authedUser();

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'This is a test comment',
                'is_internal' => false,
            ]);

        $response->assertCreated()
            ->assertJsonPath('body', 'This is a test comment');
    }

    public function test_can_list_comments_for_ticket(): void
    {
        [$user, $org, $token] = $this->authedUser();

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        Comment::factory()->count(3)->create([
            'ticket_id' => $ticket->id,
            'author_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/tickets/{$ticket->id}/comments");

        $response->assertOk();
        $this->assertCount(3, $response->json());
    }
}
