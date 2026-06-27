<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        return response()->json(
            $ticket->activityLogs()
                ->with('user')
                ->orderBy('created_at')
                ->get()
        );
    }
}
