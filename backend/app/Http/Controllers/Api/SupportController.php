<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerTicket;
use Illuminate\Http\Request;
use Termwind\Components\Raw;

class SupportController extends Controller
{
    //
    public function store(Request $request)  {
        $request->validate([
            'issue'=>'required|max:225',
            'description'=>'required',
        ]);
        CustomerTicket::create([
            'user_id'=>$request->user()->id,
            'description'=>$request->description,
            'issue'=>$request->issue
        ]);
        return response()->json([
            'message'=>'Support request submitted successfully! 🎉 Our team will reach out to you shortly.'
        ]);
    }
    public function mydata(Request $request){
        return response()->json([
            'data'=>CustomerTicket::where('user_id',$request->user()->id)->get()
        ]);
    }
}
