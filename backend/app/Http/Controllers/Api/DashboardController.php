<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function metrics(Request $request)
    {
        $orgId = $request->user()->organization_id;

        // Date range filter
        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to = $request->get('to', now()->toDateString());

        $baseQuery = Ticket::where('organization_id', $orgId)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        // Volume by status
        $byStatus = (clone $baseQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Volume by priority
        $byPriority = (clone $baseQuery)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority');

        // Average resolution time (hours) for resolved/closed tickets
        // Compatible with both SQLite and MySQL
        $resolvedTickets = (clone $baseQuery)
            ->whereIn('status', ['resolved', 'closed'])
            ->get(['created_at', 'updated_at']);

        $avgResolution = 0;
        if ($resolvedTickets->isNotEmpty()) {
            $totalSeconds = $resolvedTickets->sum(function ($t) {
                return $t->created_at->diffInSeconds($t->updated_at);
            });
            $avgResolution = round(($totalSeconds / $resolvedTickets->count()) / 3600, 1);
        }

        // Tickets per day (last 14 days)
        $perDay = Ticket::where('organization_id', $orgId)
            ->whereDate('created_at', '>=', now()->subDays(14))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // Per-agent performance
        $agentStats = User::where('organization_id', $orgId)
            ->whereIn('role', ['agent', 'admin'])
            ->select('id', 'name', 'email')
            ->selectRaw('(
                SELECT COUNT(*) FROM tickets
                WHERE tickets.assignee_id = users.id
                AND tickets.organization_id = ?
                AND tickets.deleted_at IS NULL
            ) as assigned_total', [$orgId])
            ->selectRaw('(
                SELECT COUNT(*) FROM tickets
                WHERE tickets.assignee_id = users.id
                AND tickets.organization_id = ?
                AND tickets.deleted_at IS NULL
                AND tickets.status IN ("open", "pending")
            ) as assigned_open', [$orgId])
            ->selectRaw('(
                SELECT COUNT(*) FROM tickets
                WHERE tickets.assignee_id = users.id
                AND tickets.organization_id = ?
                AND tickets.deleted_at IS NULL
                AND tickets.status IN ("resolved", "closed")
            ) as assigned_resolved', [$orgId])
            ->get();

        // Totals
        $total = $baseQuery->count();
        $open = (clone $baseQuery)->where('status', 'open')->count();
        $pending = (clone $baseQuery)->where('status', 'pending')->count();
        $resolved = (clone $baseQuery)->where('status', 'resolved')->count();
        $closed = (clone $baseQuery)->where('status', 'closed')->count();

        return response()->json([
            'totals' => [
                'all' => $total,
                'open' => $open,
                'pending' => $pending,
                'resolved' => $resolved,
                'closed' => $closed,
            ],
            'avg_resolution_hours' => round($avgResolution ?? 0, 1),
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'tickets_per_day' => $perDay,
            'agent_stats' => $agentStats,
        ]);
    }
}
