<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function authedUser(): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $org, $token];
    }

    public function test_can_assign_ticket_to_org_member(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $agent = User::factory()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/tickets/{$ticket->id}", [
                'assignee_id' => $agent->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('assignee_id', $agent->id);
    }

    public function test_cannot_assign_ticket_to_user_outside_org(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $otherOrg = Organization::factory()->create();
        $outsider = User::factory()->create(['organization_id' => $otherOrg->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/tickets/{$ticket->id}", [
                'assignee_id' => $outsider->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_can_unassign_ticket(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $agent = User::factory()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'assignee_id' => $agent->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/tickets/{$ticket->id}", [
                'assignee_id' => null,
            ]);

        $response->assertOk()
            ->assertJsonPath('assignee_id', null);
    }

    public function test_can_list_org_members(): void
    {
        [$user, $org, $token] = $this->authedUser();
        User::factory()->count(3)->create(['organization_id' => $org->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/org/members');

        $response->assertOk();
        // 4 members: original user + 3 created
        $this->assertCount(4, $response->json());
    }

    public function test_org_members_list_is_tenant_scoped(): void
    {
        [$user1, $org1, $token1] = $this->authedUser();
        [$user2, $org2, $token2] = $this->authedUser();

        User::factory()->count(2)->create(['organization_id' => $org1->id]);
        User::factory()->count(5)->create(['organization_id' => $org2->id]);

        $response = $this->withHeader('Authorization', "Bearer $token1")
            ->getJson('/api/org/members');

        $response->assertOk();
        // 3 members: user1 + 2 created
        $this->assertCount(3, $response->json());
    }
}
