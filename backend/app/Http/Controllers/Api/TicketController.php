<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::query()
            ->with(['requester', 'assignee']);

        if ($search = $request->get('search')) {
            $query->where('subject', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return response()->json(
            $query->latest()->paginate(15)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'in:low,medium,high,urgent',
        ]);

        $ticket = Ticket::create([
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            'requester_id' => $request->user()->id,
            'organization_id' => $request->user()->organization_id,
        ]);

        return response()->json($ticket->load(['requester', 'assignee']), 201);
    }

    public function show(Ticket $ticket)
    {
        return response()->json($ticket->load(['requester', 'assignee', 'comments.author']));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:open,pending,resolved,closed',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'assignee_id' => 'sometimes|nullable|exists:users,id',
        ]);

        $ticket->update($data);

        return response()->json($ticket->load(['requester', 'assignee']));
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted'], 200);
    }
}
