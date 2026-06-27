<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    public function test_can_list_notifications(): void
    {
        [$user, $org, $token] = $this->authedUser();
        Notification::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/notifications');

        $response->assertOk();
        $this->assertCount(3, $response->json());
    }

    public function test_unread_count_endpoint(): void
    {
        [$user, $org, $token] = $this->authedUser();
        Notification::factory()->count(2)->create(['user_id' => $user->id, 'is_read' => false]);
        Notification::factory()->create(['user_id' => $user->id, 'is_read' => true]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJsonPath('count', 2);
    }

    public function test_can_mark_notification_read(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $notif = Notification::factory()->create(['user_id' => $user->id, 'is_read' => false]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/notifications/{$notif->id}/read");

        $response->assertOk()
            ->assertJsonPath('is_read', true);
    }

    public function test_can_mark_all_read(): void
    {
        [$user, $org, $token] = $this->authedUser();
        Notification::factory()->count(3)->create(['user_id' => $user->id, 'is_read' => false]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/notifications/read-all');

        $response->assertOk();
        $this->assertEquals(0, Notification::where('user_id', $user->id)->where('is_read', false)->count());
    }

    public function test_notification_sent_on_status_change(): void
    {
        [$admin, $org, $token] = $this->authedUser('admin');
        $requester = User::factory()->create(['organization_id' => $org->id]);

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $requester->id,
            'status' => 'open',
        ]);

        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/tickets/{$ticket->id}", ['status' => 'resolved']);

        // Requester should have a notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $requester->id,
            'ticket_id' => $ticket->id,
            'type' => 'status_changed',
        ]);
    }

    public function test_notification_sent_on_assignment(): void
    {
        [$admin, $org, $token] = $this->authedUser('admin');
        $agent = User::factory()->create(['organization_id' => $org->id, 'role' => 'agent']);

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $admin->id,
        ]);

        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/tickets/{$ticket->id}", ['assignee_id' => $agent->id]);

        // Agent should be notified
        $this->assertDatabaseHas('notifications', [
            'user_id' => $agent->id,
            'ticket_id' => $ticket->id,
            'type' => 'assignee_id_changed',
        ]);
    }

    public function test_notifications_only_visible_to_owner(): void
    {
        [$user, $org, $token] = $this->authedUser();
        $other = User::factory()->create(['organization_id' => $org->id]);

        Notification::factory()->create(['user_id' => $other->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/notifications');

        $response->assertOk();
        $this->assertCount(0, $response->json());
    }

    public function test_no_notification_to_actor(): void
    {
        [$admin, $org, $token] = $this->authedUser('admin');
        $requester = User::factory()->create(['organization_id' => $org->id]);

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $requester->id,
            'assignee_id' => $admin->id,
        ]);

        // Admin changes status — should NOT notify themselves
        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/tickets/{$ticket->id}", ['status' => 'pending']);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $admin->id,
            'ticket_id' => $ticket->id,
            'type' => 'status_changed',
        ]);
    }
}
