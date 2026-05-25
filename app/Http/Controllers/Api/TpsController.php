<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tps;
use Illuminate\Http\Request;

class TpsController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Tps::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'accepted_waste_types' => 'nullable|array',
            'accepted_waste_types.*' => 'string|in:organik,anorganik,elektronik',
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i|after:open_time',
            'contact_phone' => 'nullable|string|max:30',
            'contact_social_media' => 'nullable|string|max:255',
        ]);

        if (isset($validated['accepted_waste_types'])) {
            $validated['accepted_waste_types'] = array_values(array_unique($validated['accepted_waste_types']));
        }

        $tps = Tps::create($validated);

        return response()->json([
            'success' => true,
            'data' => $tps
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $tps = Tps::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'accepted_waste_types' => 'nullable|array',
            'accepted_waste_types.*' => 'string|in:organik,anorganik,elektronik',
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i|after:open_time',
            'contact_phone' => 'nullable|string|max:30',
            'contact_social_media' => 'nullable|string|max:255',
        ]);

        if (isset($validated['accepted_waste_types'])) {
            $validated['accepted_waste_types'] = array_values(array_unique($validated['accepted_waste_types']));
        }

        $tps->update(collect($validated)->only([
            'name',
            'address',
            'latitude',
            'longitude',
            'accepted_waste_types',
            'open_time',
            'close_time',
            'contact_phone',
            'contact_social_media',
        ])->all());

        return response()->json([
            'success' => true,
            'data' => $tps
        ]);
    }

    public function destroy($id)
    {
        Tps::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'TPS deleted'
        ]);
    }
}
