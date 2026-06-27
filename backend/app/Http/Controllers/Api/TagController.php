<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Tag::query()->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'sometimes|string|max:20',
        ]);

        $tag = Tag::firstOrCreate(
            [
                'organization_id' => $request->user()->organization_id,
                'name' => $data['name'],
            ],
            [
                'color' => $data['color'] ?? 'gray',
            ]
        );

        return response()->json($tag, 201);
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:50',
            'color' => 'sometimes|string|max:20',
        ]);

        $tag->update($data);

        return response()->json($tag);
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return response()->json(['message' => 'Tag deleted']);
    }
}
