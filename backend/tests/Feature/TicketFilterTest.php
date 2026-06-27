<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFilterTest extends TestCase
{
    use RefreshDatabase;

    private function authedUser(): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $org, $token];
    }

    public function test_filter_by_priority(): void
    {
        [$user, $org, $token] = $this->authedUser();

        Ticket::factory()->create([
            'organization_id' => $org->id, 'requester_id' => $user->id, 'priority' => 'high',
        ]);
        Ticket::factory()->create([
            'organization_id' => $org->id, 'requester_id' => $user->id, 'priority' => 'low',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/tickets?priority=high');

        $response->assertOk();
        $this->assertEquals(1, $response->json('total'));
    }

    public function test_filter_by_multiple_statuses(): void
    {
        [$user, $org, $token] = $this->authedUser();

        Ticket::factory()->create([
            'organization_id' => $org->id, 'requester_id' => $user->id, 'status' => 'open',
        ]);
        Ticket::factory()->create([
            'organization_id' => $org->id, 'requester_id' => $user->id, 'status' => 'pending',
        ]);
        Ticket::factory()->create([
            'organization_id' => $org->id, 'requester_id' => $user->id, 'status' => 'closed',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/tickets?status=open,pending');

        $response->assertOk();
        $this->assertEquals(2, $response->json('total'));
    }

    public function test_filter_unassigned_tickets(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $agent = User::factory()->create(['organization_id' => $org->id]);

        Ticket::factory()->create([
            'organization_id' => $org->id, 'requester_id' => $user->id, 'assignee_id' => null,
        ]);
        Ticket::factory()->create([
            'organization_id' => $org->id, 'requester_id' => $user->id, 'assignee_id' => $agent->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/tickets?assignee_id=unassigned');

        $response->assertOk();
        $this->assertEquals(1, $response->json('total'));
    }

    public function test_search_by_subject(): void
    {
        [$user, $org, $token] = $this->authedUser();

        Ticket::factory()->create([
            'organization_id' => $org->id, 'requester_id' => $user->id, 'subject' => 'Login page broken',
        ]);
        Ticket::factory()->create([
            'organization_id' => $org->id, 'requester_id' => $user->id, 'subject' => 'Payment issue',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/tickets?search=login');

        $response->assertOk();
        $this->assertEquals(1, $response->json('total'));
    }

    public function test_search_by_description(): void
    {
        [$user, $org, $token] = $this->authedUser();

        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'subject' => 'Issue',
            'description' => 'The database connection keeps timing out',
        ]);
        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'subject' => 'Other',
            'description' => 'Everything is fine',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/tickets?search=database');

        $response->assertOk();
        $this->assertEquals(1, $response->json('total'));
    }
}
