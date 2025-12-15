<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserFeedbackController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $user->feedback = $data['feedback'];
        $user->save();

        return response()->json([
            'message' => 'Feedback saved successfully.',
        ]);
    }
}
