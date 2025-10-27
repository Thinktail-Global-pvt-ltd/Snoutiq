<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerTicket;
use App\Models\GroomerBooking;
use App\Models\GroomerProfile;
use App\Models\User;
use Illuminate\Contracts\View\View;

class AdminPanelController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'total_bookings' => GroomerBooking::count(),
            'total_supports' => CustomerTicket::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    public function users(): View
    {
        $users = User::orderByDesc('created_at')->get();

        return view('admin.users', compact('users'));
    }

    public function bookings(): View
    {
        $bookings = GroomerBooking::with(['groomerEmployee', 'user'])->orderByDesc('created_at')->get();

        return view('admin.bookings', compact('bookings'));
    }

    public function supports(): View
    {
        $supports = CustomerTicket::with('user')->orderByDesc('created_at')->get();

        return view('admin.supports', compact('supports'));
    }

    public function serviceProviderProfile(User $user): View
    {
        $profile = GroomerProfile::where('user_id', $user->id)->first();

        return view('admin.sp_profile', compact('profile'));
    }
}
