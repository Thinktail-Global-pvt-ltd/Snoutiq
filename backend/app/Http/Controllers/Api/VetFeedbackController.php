<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VetFeedback;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VetFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = VetFeedback::query()->orderByDesc('id');

        foreach (['vet_id', 'user_id', 'pet_id', 'rating'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, (int) $request->query($filter));
            }
        }
        if ($request->filled('channel_name')) {
            $query->where('channel_name', (string) $request->query('channel_name'));
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vet_id' => ['required', 'integer', 'exists:doctors,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'pet_id' => ['nullable', 'integer', 'exists:pets,id'],
            'channel_name' => ['nullable', 'string', 'max:191'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:50'],
            'meta' => ['nullable', 'array'],
        ]);

        if (! array_key_exists('rating', $data) && ! array_key_exists('feedback', $data)) {
            throw ValidationException::withMessages([
                'rating' => ['Either rating or feedback is required.'],
            ]);
        }

        $feedback = VetFeedback::create($data);

        return response()->json([
            'message' => 'Vet feedback created successfully.',
            'data' => $feedback,
        ], 201);
    }
}
