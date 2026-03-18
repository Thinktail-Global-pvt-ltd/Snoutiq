<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PetFeedback;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PetFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = PetFeedback::query()->orderByDesc('id');

        foreach (['pet_id', 'vet_id', 'user_id', 'rating'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, (int) $request->query($filter));
            }
        }
        $channelFilter = $request->query('channel_name', $request->query('channelName'));
        if (is_string($channelFilter) && trim($channelFilter) !== '') {
            $query->where('channel_name', trim($channelFilter));
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pet_id' => ['required', 'integer', 'exists:pets,id'],
            'vet_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'channel_name' => ['nullable', 'string', 'max:191'],
            'channelName' => ['nullable', 'string', 'max:191'],
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

        $resolvedChannelName = trim((string) (
            $data['channel_name']
            ?? $data['channelName']
            ?? data_get($data, 'meta.channel_name')
            ?? data_get($data, 'meta.channelName')
            ?? $request->input('channel_name')
            ?? $request->input('channelName')
            ?? ''
        ));
        unset($data['channelName']);
        if ($resolvedChannelName !== '') {
            $data['channel_name'] = $resolvedChannelName;
        }

        $feedback = PetFeedback::create($data);

        return response()->json([
            'message' => 'Pet feedback created successfully.',
            'data' => $feedback,
        ], 201);
    }
}
