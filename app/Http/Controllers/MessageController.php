<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
use App\Events\PrivateMessageEvent;


class MessageController extends Controller
{
    //


    public function conversation($userId) {
        $users = User::where('id', '!=', Auth::id())->get();
        $friendInfo = User::findOrFail($userId);
        $myInfo = User::find(Auth::id());
      //  $groups = MessageGroup::get();

        $this->data['users'] = $users;
        $this->data['friendInfo'] = $friendInfo;
        $this->data['myInfo'] = $myInfo;
        $this->data['users'] = $users;

        //$messages = DB::select('select messages.message as message,user_messages.sender_id as senderId,user_messages.receiver_id as receiverId from user_messages join messages on messages.id = user_messages.message_id where sender_id=? and receiver_id =?',array($myInfo->id,$userId));
        $where = array('sender_id',Auth::id());
        $or_where = array('sender_id',$userId);
        $messages = DB::select('SELECT messages.id as id FROM `messages` join user_messages on user_messages.message_id = messages.id  where (user_messages.sender_id=? and user_messages.receiver_id=?) or (user_messages.sender_id=? and user_messages.receiver_id=?)',array(Auth::id(),$userId,$userId,Auth::id()));
        
        $messages_array = array();
        if($messages){
        foreach($messages as $key=>$val){
         $res =  $this->myMessages($val->id,Auth::id());

         if(!empty($res)){
          $messages_array[] = array('message'=>$res,'position'=>'right');
         }else{
          $result = $this->otherMessages($val->id,$userId);
          $messages_array[] = array('message'=>$result,'position'=>'left');
         }
        }
    }

        $this->data['messages'] = $messages_array;
         



       // $this->data['groups'] = $groups;

        return view('message.conversation', $this->data);
    }


    function myMessages($id,$mm){
      $message = DB::table('messages')->join('user_messages', 'user_messages.message_id', '=', 'messages.id')->where('message_id',$id)->where('sender_id',$mm)->pluck('message')->first();
      return $message;
    }

    function otherMessages($id,$mm){
        $message = DB::table('messages')->join('user_messages', 'user_messages.message_id', '=', 'messages.id')->where('message_id',$id)->where('sender_id',$mm)->pluck('message')->first();
        return $message;
    }


    public function sendMessage(Request $request) {
        $request->validate([
           'message' => 'required',
           'receiver_id' => 'required'
        ]);

        $sender_id = Auth::id();
        $receiver_id = $request->receiver_id;

        $message = new Message();
        $message->message = $request->message;

        if ($message->save()) {
            try {
                $message->users()->attach($sender_id, ['receiver_id' => $receiver_id]);
                $sender = User::where('id', '=', $sender_id)->first();

                $data = [];
                $data['sender_id'] = $sender_id;
                $data['sender_name'] = $sender->name;
                $data['receiver_id'] = $receiver_id;
                $data['content'] = $message->message;
                $data['created_at'] = $message->created_at;
                $data['message_id'] = $message->id;

                event(new PrivateMessageEvent($data));

                return response()->json([
                   'data' => $data,
                   'success' => true,
                    'message' => 'Message sent successfully'
                ]);
            } catch (\Exception $e) {
                $message->delete();
            }
        }
    }
}
