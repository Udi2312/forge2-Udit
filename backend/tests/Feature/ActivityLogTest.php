<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
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

    public function test_activity_log_records_ticket_creation(): void
    {
        [$user, $org, $token] = $this->authedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/tickets', [
                'subject' => 'New Issue',
                'description' => 'Something is broken',
            ]);

        $ticketId = $response->json('id');

        $this->assertDatabaseHas('activity_logs', [
            'ticket_id' => $ticketId,
            'event' => 'created',
            'user_id' => $user->id,
        ]);
    }

    public function test_activity_log_records_status_change(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'status' => 'open',
        ]);

        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/tickets/{$ticket->id}", ['status' => 'resolved']);

        $this->assertDatabaseHas('activity_logs', [
            'ticket_id' => $ticket->id,
            'event' => 'status_changed',
            'field' => 'status',
            'old_value' => 'open',
            'new_value' => 'resolved',
        ]);
    }

    public function test_activity_log_records_assignment(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $agent = User::factory()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/tickets/{$ticket->id}", ['assignee_id' => $agent->id]);

        $this->assertDatabaseHas('activity_logs', [
            'ticket_id' => $ticket->id,
            'event' => 'assigned',
            'field' => 'assignee_id',
            'new_value' => (string) $agent->id,
        ]);
    }

    public function test_activity_log_records_priority_change(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'priority' => 'low',
        ]);

        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/tickets/{$ticket->id}", ['priority' => 'urgent']);

        $this->assertDatabaseHas('activity_logs', [
            'ticket_id' => $ticket->id,
            'event' => 'priority_changed',
            'field' => 'priority',
            'old_value' => 'low',
            'new_value' => 'urgent',
        ]);
    }

    public function test_can_fetch_activity_timeline(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        ActivityLog::factory()->count(3)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/tickets/{$ticket->id}/activity");

        $response->assertOk();
        $this->assertCount(3, $response->json());
    }

    public function test_no_duplicate_log_when_value_unchanged(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'status' => 'open',
        ]);

        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/tickets/{$ticket->id}", ['status' => 'open']);

        // Should NOT create a status_changed log
        $this->assertDatabaseMissing('activity_logs', [
            'ticket_id' => $ticket->id,
            'event' => 'status_changed',
        ]);
    }
}
