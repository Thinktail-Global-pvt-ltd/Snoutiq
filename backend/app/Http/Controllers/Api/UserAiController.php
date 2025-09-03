<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserAiChat;
use App\Models\UserAiChatHistory;
use App\Models\UserProfile;
use App\Models\UserPet;
 use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class UserAiController extends Controller
{
    //
    private static function system_prompt($uid){
        $pets = json_encode(UserPet::where('user_id',$uid)->get());
       return $system_prompt = '
    You are a helpful pet consultation assistant. Follow these rules strictly:

SCOPE: Only answer pet-related questions (dogs, cats, birds, fish, rabbits, etc.)
Our system is designed to help users about pets only. And User registers pet on our site.
RESPONSE FORMAT:
- Bold the all texts about consultation or consult a vet
- Do not use role name in response like Assistant: or User:
- Use user\'s preffered language like english, hinglish.

SAFETY RULES:
- Never diagnose medical conditions
- Always recommend vet consultation for health concerns
- Use phrases like "appears to be", "might indicate", "consider consulting"
- For behavioral issues, suggest professional trainers when appropriate
- For grooming issues, suggest professional groomers when needed

RESPONSE STYLE:
- Keep responses under 350 words
- Be clear and actionable
- Use friendly but professional tone
- Structure with clear sections

User System Registered Pets:
'.$pets.'
';}
    public function start(Request $request){
        $request->validate([
            'first_message'=>'required|max:255'
        ]);
     $user_ai_chat=   UserAiChat::create([
            'title'=>substr($request->first_message,0,60),
            'user_id'=>$request->user()->id,'token'=>md5(time()."-".$request->user()->id)
        ]);
        self::ask_ai($request->user()->id,$user_ai_chat->id,$request->first_message);
        return response()->json([
            'token'=>$user_ai_chat->token
        ]);
    }
    public function history(Request $request)  {
        return response()->json(
            [
                'data'=>UserAiChat::where('user_id',$request->user()->id)->orderBy('id','desc')->get()
            ]
            );
    }
  public function chats($token,Request $request)  {
    $user_ai_chat = UserAiChat::where('token',$token)->where('user_id',$request->user()->id)->first();
    if(!$user_ai_chat){
        return response()->json([
            'message'=>'Not found!'
        ],404);
    }
    return response()->json(
        [
            'chatData'=>$user_ai_chat,
            'messages'=>UserAiChatHistory::where('user_ai_chat_id',$user_ai_chat->id)->where('user_id',$request->user()->id)->get()
        ]
        );
  }
  public function rated($hist_id,Request $request){
    UserAiChatHistory::where('id',$hist_id)->where('user_id',$request->user()->id)->first()->update(['rated'=>$request->rated]);
    return response()->json([
        'message'=>"Thanks for your feedback"
    ]);
  }
  public function postChat($token,Request $request)  {
     $request->validate([
            'message'=>'required|max:255'
        ]);
    $user_ai_chat = UserAiChat::where('token',$token)->where('user_id',$request->user()->id)->first();
    if(!$user_ai_chat){
        return response()->json([
            'message'=>'Not found!'
        ],404);
    }
      $res=      self::ask_ai($request->user()->id,$user_ai_chat->id,$request->message);

    return response()->json(
        [
           
            'response'=>$res,
            'id'=>UserAiChatHistory::where('user_ai_chat_id',$user_ai_chat->id)->where('user_id',$request->user()->id)->orderBy('id','desc')->first()->id
        ]
        );
  }
  
    public function ask_ai($uid,$chat_id,$message){
        $api_key = env('GEMINI_API_KEY');
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$api_key}";
$parts=[];
$parts[]=["text"=>"System: ".self::system_prompt($uid)];
foreach(UserAiChatHistory::where('user_ai_chat_id',$chat_id)->get() as $oldMessages){
    $parts[]=["text"=>$oldMessages->type.":".$oldMessages->message];
}
$parts[]=["text"=>"User:".$message];
 $response = Http::withHeaders([
        'Content-Type' => 'application/json'
    ])->post($apiUrl, [
        'contents' => [
            [
                'parts' => $parts
            ]
        ]
    ]);

    if ($response->successful()) {
        UserAiChatHistory::create(
            [
                'type'=>'User',
'message'=>$message,
'user_id'=>$uid,
'user_ai_chat_id'=>$chat_id

            ]
            );
               UserAiChatHistory::create(
            [
                'type'=>'Assistant',
'message'=>$response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null,
'user_id'=>$uid,
'user_ai_chat_id'=>$chat_id

            ]
            );
        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    }
}
