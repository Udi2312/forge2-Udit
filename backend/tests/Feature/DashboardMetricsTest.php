<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_endpoint_returns_aggregates(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'admin']);
        $token = $user->createToken('test')->plainTextToken;

        Ticket::factory()->count(3)->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'status' => 'resolved',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/dashboard/metrics');

        $response->assertOk()
            ->assertJsonStructure([
                'totals' => ['all', 'open', 'pending', 'resolved', 'closed'],
                'avg_resolution_hours',
                'by_status',
                'by_priority',
                'tickets_per_day',
                'agent_stats',
            ]);

        $this->assertEquals(4, $response->json('totals.all'));
        $this->assertEquals(3, $response->json('totals.open'));
        $this->assertEquals(1, $response->json('totals.resolved'));
    }

    public function test_metrics_are_tenant_scoped(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id, 'role' => 'admin']);
        $token1 = $user1->createToken('test')->plainTextToken;

        Ticket::factory()->count(5)->create([
            'organization_id' => $org1->id,
            'requester_id' => $user1->id,
        ]);
        Ticket::factory()->count(10)->create([
            'organization_id' => $org2->id,
            'requester_id' => User::factory()->create(['organization_id' => $org2->id]),
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token1")
            ->getJson('/api/dashboard/metrics');

        $response->assertOk();
        $this->assertEquals(5, $response->json('totals.all'));
    }

    public function test_metrics_supports_date_range(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'admin']);
        $token = $user->createToken('test')->plainTextToken;

        // Old ticket (outside range)
        $old = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'created_at' => now()->subDays(60),
        ]);

        // Recent ticket
        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/dashboard/metrics?from=' . now()->subDays(7)->toDateString());

        $response->assertOk();
        $this->assertEquals(1, $response->json('totals.all'));
    }

    public function test_agent_stats_included(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id, 'role' => 'admin']);
        $agent = User::factory()->create(['organization_id' => $org->id, 'role' => 'agent']);
        $token = $admin->createToken('test')->plainTextToken;

        Ticket::factory()->count(2)->create([
            'organization_id' => $org->id,
            'requester_id' => $admin->id,
            'assignee_id' => $agent->id,
            'status' => 'open',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/dashboard/metrics');

        $response->assertOk();

        $agents = collect($response->json('agent_stats'));
        $agentStat = $agents->firstWhere('id', $agent->id);
        $this->assertNotNull($agentStat);
        // Check for the count keys (Laravel may use different naming)
        $this->assertTrue(
            ($agentStat['assigned_total'] ?? $agentStat['assigned_tickets_count'] ?? 0) == 2,
            'Expected assigned count of 2. Keys: ' . json_encode(array_keys($agentStat))
        );
    }
}
