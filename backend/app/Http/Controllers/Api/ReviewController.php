<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Review;

class ReviewController extends Controller
{
    // GET /api/reviews?user_id=&doctor_id=
    public function index(Request $request)
    {
        $query = Review::query()->orderByDesc('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->query('user_id'));
        }
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', (int) $request->query('doctor_id'));
        }

        $reviews = $query->paginate(20);
        return response()->json($reviews);
    }

    // POST /api/reviews
    public function store(Request $request)
    {
        $data = $request->only(['user_id', 'doctor_id', 'points', 'comment']);

        $validator = Validator::make($data, [
            'user_id'   => 'required|integer|min:1',
            'doctor_id' => 'required|integer|min:1',
            'points'    => 'required|integer|min:1|max:5',
            'comment'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $review = Review::create($data);

        return response()->json([
            'message' => 'Review created',
            'data'    => $review,
        ], 201);
    }
}

