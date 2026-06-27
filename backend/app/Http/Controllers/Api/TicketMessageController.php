<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class TicketMessageController extends Controller
{
    /**
     * List messages for a ticket.
     * Internal notes are hidden from users with 'customer' role.
     */
    public function index(Request $request, Ticket $ticket)
    {
        $query = $ticket->messages()->with('user');

        // Customers can only see public messages
        if ($request->user()->role === 'customer') {
            $query->where('is_internal', false);
        }

        return response()->json(
            $query->orderBy('created_at')->get()
        );
    }

    public function store(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'body' => 'required|string',
            'is_internal' => 'boolean',
        ]);

        $isInternal = $data['is_internal'] ?? false;

        // Only agents and admins can post internal notes
        if ($isInternal && !in_array($request->user()->role, ['agent', 'admin'])) {
            return response()->json([
                'message' => 'You do not have permission to post internal notes.',
            ], 403);
        }

        $message = $ticket->messages()->create([
            'body' => $data['body'],
            'is_internal' => $isInternal,
            'user_id' => $request->user()->id,
        ]);

        ActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'event' => 'message_sent',
            'field' => 'message_type',
            'new_value' => $isInternal ? 'internal' : 'public',
        ]);

        return response()->json($message->load('user'), 201);
    }
}
