<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    private function authedUser(string $role = 'admin'): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => $role,
        ]);
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $org, $token];
    }

    public function test_can_list_messages(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);
        TicketMessage::factory()->count(3)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/tickets/{$ticket->id}/messages");

        $response->assertOk();
        $this->assertCount(3, $response->json());
    }

    public function test_can_send_public_reply(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/tickets/{$ticket->id}/messages", [
                'body' => 'This is a public reply',
                'is_internal' => false,
            ]);

        $response->assertCreated()
            ->assertJsonPath('body', 'This is a public reply')
            ->assertJsonPath('is_internal', false);
    }

    public function test_agent_can_send_internal_note(): void
    {
        [$user, $org, $token] = $this->authedUser('agent');
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/tickets/{$ticket->id}/messages", [
                'body' => 'Internal note for the team',
                'is_internal' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('is_internal', true);
    }

    public function test_customer_cannot_send_internal_note(): void
    {
        [$user, $org, $token] = $this->authedUser('customer');
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/tickets/{$ticket->id}/messages", [
                'body' => 'Trying to post internal',
                'is_internal' => true,
            ]);

        $response->assertForbidden();
    }

    public function test_customer_cannot_see_internal_notes(): void
    {
        [$admin, $org, $adminToken] = $this->authedUser('admin');
        $customer = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'customer',
        ]);
        $customerToken = $customer->createToken('cust')->plainTextToken;

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $customer->id,
        ]);

        // Admin posts internal note
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'body' => 'Secret internal discussion',
            'is_internal' => true,
        ]);

        // Public reply
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'body' => 'Public reply',
            'is_internal' => false,
        ]);

        // Customer should only see 1 message (public)
        $response = $this->withHeader('Authorization', "Bearer $customerToken")
            ->getJson("/api/tickets/{$ticket->id}/messages");

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertEquals('Public reply', $response->json()[0]['body']);
    }

    public function test_messages_are_chronological(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $m1 = TicketMessage::create([
            'ticket_id' => $ticket->id, 'user_id' => $user->id,
            'body' => 'First', 'is_internal' => false,
        ]);
        sleep(0); // ensure ordering
        $m2 = TicketMessage::create([
            'ticket_id' => $ticket->id, 'user_id' => $user->id,
            'body' => 'Second', 'is_internal' => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/tickets/{$ticket->id}/messages");

        $response->assertOk();
        $messages = $response->json();
        $this->assertEquals('First', $messages[0]['body']);
        $this->assertEquals('Second', $messages[1]['body']);
    }
}
