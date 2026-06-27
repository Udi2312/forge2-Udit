<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaPolicyTest extends TestCase
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

    public function test_admin_can_create_sla_policy(): void
    {
        [$user, $org, $token] = $this->authedUser('admin');

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/sla-policies', [
                'name' => 'Urgent SLA',
                'priority' => 'urgent',
                'response_time_minutes' => 60,
                'resolution_time_minutes' => 360,
            ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Urgent SLA')
            ->assertJsonPath('priority', 'urgent');
    }

    public function test_non_admin_cannot_create_sla_policy(): void
    {
        [$user, $org, $token] = $this->authedUser('agent');

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/sla-policies', [
                'name' => 'Test SLA',
                'priority' => 'low',
                'response_time_minutes' => 480,
                'resolution_time_minutes' => 2880,
            ]);

        $response->assertForbidden();
    }

    public function test_can_list_sla_policies(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $priorities = ['low', 'medium', 'high'];
        foreach ($priorities as $p) {
            SlaPolicy::factory()->create([
                'organization_id' => $org->id,
                'priority' => $p,
            ]);
        }

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/sla-policies');

        $response->assertOk();
        $this->assertCount(3, $response->json());
    }

    public function test_sla_policies_are_tenant_scoped(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $otherOrg = Organization::factory()->create();
        SlaPolicy::factory()->count(2)->create(['organization_id' => $otherOrg->id]);
        SlaPolicy::factory()->create(['organization_id' => $org->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/sla-policies');

        $response->assertOk();
        $this->assertCount(1, $response->json());
    }

    public function test_ticket_sla_status_breached(): void
    {
        [$user, $org, $token] = $this->authedUser();

        SlaPolicy::create([
            'organization_id' => $org->id,
            'name' => 'Urgent SLA',
            'priority' => 'urgent',
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 120,
            'is_active' => true,
        ]);

        // Create a ticket created 3 hours ago (past both response and resolution)
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'priority' => 'urgent',
            'status' => 'open',
            'created_at' => now()->subHours(3),
        ]);

        $sla = $ticket->slaStatus();
        $this->assertEquals('breached', $sla['status']);
    }

    public function test_ticket_sla_status_on_track(): void
    {
        [$user, $org, $token] = $this->authedUser();

        SlaPolicy::create([
            'organization_id' => $org->id,
            'name' => 'Medium SLA',
            'priority' => 'medium',
            'response_time_minutes' => 240,
            'resolution_time_minutes' => 1440,
            'is_active' => true,
        ]);

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $sla = $ticket->slaStatus();
        $this->assertEquals('on_track', $sla['status']);
    }

    public function test_ticket_sla_status_met_when_resolved(): void
    {
        [$user, $org, $token] = $this->authedUser();

        SlaPolicy::create([
            'organization_id' => $org->id,
            'name' => 'Low SLA',
            'priority' => 'low',
            'response_time_minutes' => 480,
            'resolution_time_minutes' => 2880,
            'is_active' => true,
        ]);

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'priority' => 'low',
            'status' => 'resolved',
        ]);

        $sla = $ticket->slaStatus();
        $this->assertEquals('met', $sla['status']);
    }

    public function test_ticket_sla_none_without_policy(): void
    {
        [$user, $org, $token] = $this->authedUser();

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $sla = $ticket->slaStatus();
        $this->assertEquals('none', $sla['status']);
    }

    public function test_admin_can_update_sla_policy(): void
    {
        [$user, $org, $token] = $this->authedUser('admin');
        $policy = SlaPolicy::factory()->create(['organization_id' => $org->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/sla-policies/{$policy->id}", [
                'response_time_minutes' => 30,
            ]);

        $response->assertOk()
            ->assertJsonPath('response_time_minutes', 30);
    }

    public function test_admin_can_delete_sla_policy(): void
    {
        [$user, $org, $token] = $this->authedUser('admin');
        $policy = SlaPolicy::factory()->create(['organization_id' => $org->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/sla-policies/{$policy->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('sla_policies', ['id' => $policy->id]);
    }
}
