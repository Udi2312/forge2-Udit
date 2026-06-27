<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function members(Request $request)
    {
        $members = User::where('organization_id', $request->user()->organization_id)
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->get();

        return response()->json($members);
    }
}
