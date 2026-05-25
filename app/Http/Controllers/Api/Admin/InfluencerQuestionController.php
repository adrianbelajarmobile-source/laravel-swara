<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InfluencerQuestion;

class InfluencerQuestionController extends Controller
{
    // GET /api/admin/influencer/questions
    public function index()
    {
        $questions = InfluencerQuestion::orderByDesc('created_at')->get();

        return response()->json($questions);
    }

    // POST /api/admin/influencer/questions
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        $question = InfluencerQuestion::create([
            'question' => $validated['question'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Pertanyaan berhasil dibuat',
            'data' => $question
        ], 201);
    }

    // GET /api/admin/influencer/questions/{id}
    public function show($id)
    {
        $question = InfluencerQuestion::findOrFail($id);

        return response()->json($question);
    }

    // PUT /api/admin/influencer/questions/{id}
    public function update(Request $request, $id)
    {
        $question = InfluencerQuestion::findOrFail($id);

        $validated = $request->validate([
            'question' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean'
        ]);

        $question->update($validated);

        return response()->json([
            'message' => 'Pertanyaan berhasil diperbarui',
            'data' => $question
        ]);
    }

    // DELETE /api/admin/influencer/questions/{id}
    public function destroy($id)
    {
        $question = InfluencerQuestion::findOrFail($id);
        $question->delete();

        return response()->json([
            'message' => 'Pertanyaan berhasil dihapus'
        ]);
    }
}
