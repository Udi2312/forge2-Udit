<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Ticket $ticket)
    {
        return response()->json(
            $ticket->comments()->with('author')->latest()->get()
        );
    }

    public function store(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'body' => 'required|string',
            'is_internal' => 'boolean',
        ]);

        $comment = $ticket->comments()->create([
            'body' => $data['body'],
            'is_internal' => $data['is_internal'] ?? false,
            'author_id' => $request->user()->id,
        ]);

        return response()->json($comment->load('author'), 201);
    }

    public function update(Request $request, Comment $comment)
    {
        $data = $request->validate([
            'body' => 'sometimes|string',
            'is_internal' => 'sometimes|boolean',
        ]);

        $comment->update($data);

        return response()->json($comment->load('author'));
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
        return response()->json(['message' => 'Comment deleted']);
    }
}
