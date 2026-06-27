<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SlaPolicy;
use Illuminate\Http\Request;

class SlaPolicyController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            SlaPolicy::where('organization_id', $request->user()->organization_id)->get()
        );
    }

    public function store(Request $request)
    {
        if (!in_array($request->user()->role, ['admin'])) {
            return response()->json(['message' => 'Only admins can manage SLA policies.'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'response_time_minutes' => 'required|integer|min:1',
            'resolution_time_minutes' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $policy = SlaPolicy::create(array_merge($data, [
            'organization_id' => $request->user()->organization_id,
        ]));

        return response()->json($policy, 201);
    }

    public function update(Request $request, SlaPolicy $slaPolicy)
    {
        if ($slaPolicy->organization_id !== $request->user()->organization_id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (!in_array($request->user()->role, ['admin'])) {
            return response()->json(['message' => 'Only admins can manage SLA policies.'], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'response_time_minutes' => 'sometimes|integer|min:1',
            'resolution_time_minutes' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        $slaPolicy->update($data);
        return response()->json($slaPolicy);
    }

    public function destroy(Request $request, SlaPolicy $slaPolicy)
    {
        if ($slaPolicy->organization_id !== $request->user()->organization_id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if (!in_array($request->user()->role, ['admin'])) {
            return response()->json(['message' => 'Only admins can manage SLA policies.'], 403);
        }

        $slaPolicy->delete();
        return response()->json(['message' => 'SLA policy deleted']);
    }
}
