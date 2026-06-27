<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Tag;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::query()
            ->with(['requester', 'assignee', 'tags']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->whereIn('status', explode(',', $status));
        }

        if ($priority = $request->get('priority')) {
            $query->whereIn('priority', explode(',', $priority));
        }

        if ($assigneeId = $request->get('assignee_id')) {
            if ($assigneeId === 'unassigned') {
                $query->whereNull('assignee_id');
            } else {
                $query->where('assignee_id', $assigneeId);
            }
        }

        if ($tagName = $request->get('tag')) {
            $query->whereHas('tags', function ($q) use ($tagName) {
                $q->where('name', $tagName);
            });
        }

        $sort = $request->get('sort', 'created_at');
        $dir = $request->get('dir', 'desc');
        $allowedSorts = ['created_at', 'updated_at', 'subject', 'status', 'priority'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $tickets = $query->latest()->paginate($request->get('per_page', 15));

        // Append SLA status to each ticket
        $tickets->getCollection()->transform(function ($ticket) {
            $ticket->sla = $ticket->slaStatus();
            return $ticket;
        });

        return response()->json($tickets);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'in:low,medium,high,urgent',
            'tag_ids' => 'array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        $ticket = Ticket::create([
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            'requester_id' => $request->user()->id,
            'organization_id' => $request->user()->organization_id,
        ]);

        if (!empty($data['tag_ids'])) {
            $ticket->tags()->attach($data['tag_ids']);
        }

        ActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'event' => 'created',
        ]);

        // Notify all agents/admins of the org about the new ticket
        $this->notifyOrgAgents($ticket, 'ticket_created', 'New ticket created', "Ticket #{$ticket->id}: {$ticket->subject}");

        $ticket->sla = $ticket->slaStatus();
        return response()->json($ticket->load(['requester', 'assignee', 'tags']), 201);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['requester', 'assignee', 'tags', 'comments.author']);
        $ticket->sla = $ticket->slaStatus();
        return response()->json($ticket);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:open,pending,resolved,closed',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'assignee_id' => 'sometimes|nullable|integer',
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        // Assignment: ensure assignee belongs to same org
        if (array_key_exists('assignee_id', $data)) {
            if ($data['assignee_id'] !== null) {
                $assignee = User::where('id', $data['assignee_id'])
                    ->where('organization_id', $request->user()->organization_id)
                    ->first();

                if (!$assignee) {
                    return response()->json([
                        'message' => 'Assignee must be a member of your organization.',
                    ], 422);
                }
            }
        }

        // Track changes for activity log + notifications
        $changes = [];
        foreach (['status', 'priority', 'assignee_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $oldVal = $ticket->$field;
                $newVal = $data[$field];
                if ((string) $oldVal !== (string) $newVal) {
                    $changes[] = [
                        'field' => $field,
                        'old_value' => $oldVal,
                        'new_value' => $newVal,
                    ];
                }
            }
        }

        $ticket->update($data);

        // Log changes + send notifications
        foreach ($changes as $change) {
            $eventLabel = match($change['field']) {
                'status' => 'status_changed',
                'priority' => 'priority_changed',
                'assignee_id' => 'assigned',
                default => 'updated',
            };

            ActivityLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'event' => $eventLabel,
                'field' => $change['field'],
                'old_value' => $change['old_value'] ? (string) $change['old_value'] : null,
                'new_value' => $change['new_value'] ? (string) $change['new_value'] : null,
            ]);

            // Send notifications
            $this->sendChangeNotification($ticket, $change['field'], $change['old_value'], $change['new_value'], $request->user());
        }

        if (array_key_exists('tag_ids', $data)) {
            $ticket->tags()->sync($data['tag_ids']);
            ActivityLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'event' => 'tagged',
            ]);
        }

        $ticket->sla = $ticket->slaStatus();
        return response()->json($ticket->load(['requester', 'assignee', 'tags']));
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted'], 200);
    }

    /**
     * Send in-app notification about a ticket change to relevant users.
     */
    private function sendChangeNotification(Ticket $ticket, string $field, $oldVal, $newVal, $actor): void
    {
        // Determine recipients: requester + assignee (excluding the actor)
        $recipientIds = collect([$ticket->requester_id, $ticket->assignee_id])
            ->filter()
            ->unique()
            ->reject(fn ($id) => $id === $actor->id)
            ->toArray();

        $titles = [
            'status' => "Status changed to {$newVal}",
            'priority' => "Priority changed to {$newVal}",
            'assignee_id' => $newVal ? 'Ticket assigned to you' : 'Ticket unassigned',
        ];

        $title = $titles[$field] ?? 'Ticket updated';

        foreach ($recipientIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'ticket_id' => $ticket->id,
                'type' => $field . '_changed',
                'title' => $title,
                'body' => "Ticket #{$ticket->id} \"{$ticket->subject}\" — {$title}",
            ]);
        }
    }

    /**
     * Notify all agents/admins in the org about a new ticket.
     */
    private function notifyOrgAgents(Ticket $ticket, string $type, string $title, string $body): void
    {
        $agents = User::where('organization_id', $ticket->organization_id)
            ->whereIn('role', ['agent', 'admin'])
            ->pluck('id');

        foreach ($agents as $agentId) {
            Notification::create([
                'user_id' => $agentId,
                'ticket_id' => $ticket->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
            ]);
        }
    }
}
