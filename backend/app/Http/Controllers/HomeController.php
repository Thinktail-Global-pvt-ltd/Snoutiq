<?php

namespace App\Http\Controllers;

use App\Models\CustomerTicket;
use App\Models\GroomerBooking;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\GroomerProfile;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }
     public function users()
    {
        $users = User::get();
        return view('admin.users',compact('users'));
    }
    public function sp_profile($id){
        $profile = GroomerProfile::where('user_id',$id)->first();
        if(!$profile){
            return abort(404);
        }
        // dd(compact('profile'));
        return view('admin.sp_profile',compact('profile'));
    }
    public function bookings() {
        $bookings = GroomerBooking::with('groomerEmployee')->with('user')->get();
        return view('admin.bookings',compact('bookings'));
    }
    public function supports()  {
        $supports = CustomerTicket::with("user")->get()
        ;
        return view('admin.supports',compact('supports'));

    }
}
