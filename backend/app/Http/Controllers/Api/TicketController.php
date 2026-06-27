<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::query()
            ->with(['requester', 'assignee', 'tags']);

        // Search: subject or description
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter: status (supports comma-separated multi-select)
        if ($status = $request->get('status')) {
            $query->whereIn('status', explode(',', $status));
        }

        // Filter: priority
        if ($priority = $request->get('priority')) {
            $query->whereIn('priority', explode(',', $priority));
        }

        // Filter: assignee_id
        if ($assigneeId = $request->get('assignee_id')) {
            if ($assigneeId === 'unassigned') {
                $query->whereNull('assignee_id');
            } else {
                $query->where('assignee_id', $assigneeId);
            }
        }

        // Filter: tag (by name)
        if ($tagName = $request->get('tag')) {
            $query->whereHas('tags', function ($q) use ($tagName) {
                $q->where('name', $tagName);
            });
        }

        // Sort
        $sort = $request->get('sort', 'created_at');
        $dir = $request->get('dir', 'desc');
        $allowedSorts = ['created_at', 'updated_at', 'subject', 'status', 'priority'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        return response()->json(
            $query->latest()->paginate($request->get('per_page', 15))
        );
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

        return response()->json($ticket->load(['requester', 'assignee', 'tags']), 201);
    }

    public function show(Ticket $ticket)
    {
        return response()->json($ticket->load(['requester', 'assignee', 'tags', 'comments.author']));
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

        $ticket->update($data);

        // Sync tags if provided
        if (array_key_exists('tag_ids', $data)) {
            $ticket->tags()->sync($data['tag_ids']);
        }

        return response()->json($ticket->load(['requester', 'assignee', 'tags']));
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted'], 200);
    }
}
