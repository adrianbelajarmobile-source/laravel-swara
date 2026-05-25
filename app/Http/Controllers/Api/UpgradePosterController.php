<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UpgradePoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UpgradePosterController extends Controller
{
    /**
     * Return all upgrade posters ordered by order ascending.
     */
    public function index()
    {
        $posters = UpgradePoster::orderBy('order', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $posters,
        ]);
    }

    /**
     * Store a new upgrade poster with image upload.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'order' => 'required|integer',
        ]);

        $imagePath = $request->file('image')->store('upgrade_posters', 'public');

        $poster = UpgradePoster::create([
            'image' => $imagePath,
            'order' => $validated['order'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Poster uploaded successfully',
            'data' => $poster,
        ], 201);
    }

    /**
     * Delete an existing upgrade poster.
     */
    public function destroy($id)
    {
        $poster = UpgradePoster::find($id);

        if (! $poster) {
            return response()->json([
                'success' => false,
                'message' => 'Poster not found',
            ], 404);
        }

        if ($poster->image && Storage::disk('public')->exists($poster->image)) {
            Storage::disk('public')->delete($poster->image);
        }

        $poster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Poster deleted successfully',
        ]);
    }
}
