<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Illuminate\Support\Str;

class ForgotPasswordSimpleController extends Controller
{
    public function sendNewPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Generate new password
        $newPassword = Str::random(10);
        $user->password = Hash::make($newPassword);
        $user->save();

        // Send the email
        Mail::raw("Your snoutiq.com new password is: $newPassword\nDon't share it with anyone. \nThanks team\nSnoutiq.com", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Your New Password');
        });

        return response()->json(['message' => 'New password sent to your email.']);
    }
}
