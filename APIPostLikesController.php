<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\PostLikes;
use DB;
use App\Notification;
use App\Http\Controllers\APINotificationController as Notifications;

class APIPostLikesController extends Controller
{
    // Use this function for Add Likes on User's Posts.
    
    public function like(Request $request)
    {
        if ($request->method() == 'POST') {
            try {
                $validator = Validator::make($request->all(), [
                    'post_id' => 'required|numeric',
                    'liked_by' => 'required|numeric',
                    'liked_to' => 'required|numeric',
                ]);
                if ($validator->fails()) {
                    return response()->json(['status_code' => 1, 'message' => $validator->errors()->first()], 406);
                }
                // DB::enableQueryLog();
                $data1 = [];

                $removeLike = DB::table('post_likes')
                    ->where('post_id', $request->post_id)
                    ->where('liked_by', $request->liked_by)
                    ->where('liked_to', $request->liked_to)
                    ->delete();
                // dd(DB::getQueryLog());
                $count_like = DB::table('post_likes')
                    ->where('post_id', $request->get('post_id'))
                    ->count();
                $data1['count_like'] = $count_like;
                $data1['like_code'] = 0;
                if ($removeLike != false) {
                    // return response()->json(['status_code' => 0, 'message' => 'Like has been removed.'], 200);
                    if($data1){
                        return response()->json(['status_code' => 0, 'message' => 'Like has been removed.','results'=>$data1], 200);
                    }
                    else{return response()->json(['status_code' => 1, 'message' => 'Oops! Something went wrong.'], 200);}
                } else {
                    $data = [];
                    $like = PostLikes::create([
                        'post_id' => $request->get('post_id'),
                        'liked_by' => $request->get('liked_by'),
                        'liked_to' => $request->get('liked_to'),
                    ]);

                    // dd($like);
                    if ($like) {
                        $count_likes = DB::table('post_likes')
                        ->where('post_id', $request->get('post_id'))
                        ->count();

                        $like_by_name = DB::table('user_detail')
                            ->where('user_id', $request->get('liked_to'))
                            ->select('name')
                            ->get();

                        $noti = new Notifications();
                        $tokens = DB::table('user_detail')
                            ->where('user_id', $request->get('liked_to'))
                            ->select('fcm_token')
                            ->get();

                        $noti_array = [
                            'title' => "Post Liked",
                            'body' => $like_by_name[0]->name . " has liked your post.",
                            'tokens' => $tokens[0]->fcm_token,
                        ];
                        $insertedId = $like->id;
                        $notification = Notification::create([
                            'followers_id' => '0',
                            'post_id' => $insertedId,
                            'user_id' => $request->get('liked_to'),
                            'post_rate_id' => '0',
                            'post_comment_id' => '0',
                            'post_report_id' => '0',
                            'status' => '1',
                            'title' =>  $noti_array['title'],
                            'type' => 'like',
                            'message' => $noti_array['body'],
                            'is_viewed' => '0', //notification only sent not viewed.
                        ]);
                        // dd($notification);
                        if ($notification) {
                            $notify_status = 0;

                            $notify = DB::table('user_detail')
                                ->select('notify_status')
                                ->where('user_id', $request->get('liked_to'))
                                ->get()->toArray();
                            // dd($notify);
                            // if ($notify[0]->notify_status == 0) {
                            //     $status = $noti->sendNotification($noti_array);
                            //     dd($notify);
                            //     if ($status == 1) {
                            //         return response()->json(['status_code' => 0, 'message' => 'Like has been Added.'], 200);
                            //     } else {
                            //         // return response()->json(['status_code' => 0, 'message' => 'Like has been Added..'], 200);
                            //     }
                            // } else {
                            //     $notify_status = 1;
                            // }
                        } else {
                            // return response()->json(['status_code' => 0, 'message' => 'Like has been Added...'], 200);
                        }
                        $data['count_likes'] = $count_likes;
                        $data['like_code'] = 1;
                        if($data){
                            return response()->json(['status_code' => 0, 'message' => 'Like has been Added...','results'=>$data], 200);
                        }
                    } else {
                        return response()->json(['status_code' => 1, 'message' => 'Oops! Something went wrong.'], 200);
                    }
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method is not allowed'], 405);
        }
    }

}
