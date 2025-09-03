<?php

namespace App\Http\Controllers\Api\Groomer;

use Illuminate\Http\Request;
use App\Models\GroomerServiceCategory;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
class ServiceCategoryController extends Controller
{
    //
   public function store(Request $request){
    $request->validate([
        'name'=>'required|max:60',

    ]);
    $data = [
        'name'=>$request->name,

'user_id'=>$request->user()->id
    ];
//  
    $GroomerServiceCategory = GroomerServiceCategory::create($data);
    
    return response()->json([
        'message'=>'Category created successfully!'
    ]);
   }
  public function get(Request $request){
    $GroomerServiceCategory = GroomerServiceCategory::where('user_id',$request->user()->id)->get();
    return response()->json([
        'data'=>$GroomerServiceCategory
    ]);
  }

   public function update($id,Request $request){
    $request->validate([
        'name'=>'required|max:60',

    ]);
    $data = [
        'name'=>$request->name,
 
    ];
//  
    $GroomerServiceCategory = GroomerServiceCategory::where('user_id',$request->user()->id)->where('id',$id)->update($data);
    
    return response()->json([
        'message'=>'Category updated successfully!'
    ]);
   }
    public function delete($id,Request $request){
  
//  
    $GroomerServiceCategory = GroomerServiceCategory::where('user_id',$request->user()->id)->where('id',$id)->delete();
    
    return response()->json([
        'message'=>'Category deleted successfully!'
    ]);
   }
}
