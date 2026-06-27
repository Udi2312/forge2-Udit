<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUser($org, $role = 'agent')
    {
        return User::factory()->create([
            'organization_id' => $org->id,
            'role' => $role,
        ]);
    }

    public function test_health_endpoint(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_user_can_register_and_get_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'organization_name' => 'Test Corp',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
    }

    public function test_user_can_login(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'email' => 'login@test.com',
            'password' => bcrypt('password123'),
            'organization_id' => $org->id,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user' => ['id'], 'token']);
    }

    public function test_authenticated_user_can_create_ticket(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createUser($org, 'customer');

        $response = $this->actingAs($user)->postJson('/api/tickets', [
            'subject' => 'Test Ticket',
            'description' => 'This is a test issue.',
            'priority' => 'high',
        ]);

        $response->assertStatus(201)
            ->assertJson(['subject' => 'Test Ticket']);
    }

    public function test_ticket_isolation_between_tenants(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = $this->createUser($orgA);
        $userB = $this->createUser($orgB);

        // Create tickets in each org
        $ticketA = Ticket::factory()->create([
            'organization_id' => $orgA->id,
            'requester_id' => $userA->id,
        ]);
        $ticketB = Ticket::factory()->create([
            'organization_id' => $orgB->id,
            'requester_id' => $userB->id,
        ]);

        // User A should only see org A's ticket
        $response = $this->actingAs($userA)->getJson('/api/tickets');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($ticketA->id, $data[0]['id']);

        // User B should only see org B's ticket
        $response = $this->actingAs($userB)->getJson('/api/tickets');
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($ticketB->id, $data[0]['id']);
    }

    public function test_user_can_add_comment_to_ticket(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createUser($org);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'This is a comment.',
            ]);

        $response->assertStatus(201)
            ->assertJson(['body' => 'This is a comment.']);
    }
}
