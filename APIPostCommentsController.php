<?php

namespace App\Http\Controllers;

use App\PostComments;
use Illuminate\Http\Request;
use Validator;
use DB;
use App\Notification;
use App\Http\Controllers\APINotificationController as Notifications;

class APIPostCommentsController extends Controller
{
    // Use this function for Displaying all comments on perticular post.
    
    public function commentList(Request $request, $id)
    {
        if ($request->method() == 'GET') {
            try {
                if (!is_numeric($id)) {
                    return response()->json(['status_code' => 1, 'message' => 'Id must be integer value.'], 406);
                }
                $comment = DB::table('post_comments')
                    ->where('post_id', $id)
                    ->orderBy('created_at', 'desc')
                    ->get()->toArray();

                if ($comment) {
                    return response()->json(['status_code' => 0, 'message' => 'Comments Found.', 'results' => $comment], 200);
                } else {
                    return response()->json(['status_code' => 1, 'message' => 'Comments Not Found.'], 200);
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method is not allowed'], 405);
        }
    }

    // Use this function for Delete comments on the posts.

    public function deleteComment(Request $request, $id)
    {
        if ($request->method() == 'GET') {
            try {
                $postcomment = DB::table('post_comments')
                    ->where('post_comment_id', $id)
                    ->delete();
                if ($postcomment !== false) {
                    return response()->json(['status_code' => 0, 'message' => 'Comment has been deleted.'], 200);
                } else {
                    return response()->json(['status_code' => 1, 'message' => 'Oops! Something went wrong.'], 200);
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method not allowed.'], 405);
        }
    }
    // Use this function for edit existing comments on the posts.

    public function editPostComments(Request $request)
    {
        if ($request->method() == 'POST') {
            try {
                $validator = Validator::make($request->all(), [
                    'post_comment_id' => 'required',
                    'post_id' => 'required',
                    'commented_by' => 'required',
                    // 'commented_on' => 'required',
                    'comment' => 'required',
                ]);

                if ($validator->fails()) {
                    return response()->json(['status_code' => 1, 'message' => $validator->errors()->first()], 406);
                }
                $comment = DB::table('post_comments')
                    ->where('post_comment_id', $request->post_comment_id)
                    ->where('post_id', $request->post_id)
                    ->where('commented_by', $request->commented_by)
                    // ->where('commented_on', $request->commented_on)
                    ->update(
                        [
                            'comment' => $request->comment,
                        ]
                    );

                if ($comment) {
                    return response()->json(['status_code' => 0, 'message' => 'Your Comment has been Successfully Updated.'], 200);
                } else {
                    return response()->json(['status_code' => 1, 'message' => 'Oops! Something went wrong.'], 200);
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method not allowed.'], 405);
        }
    }
    // used this function for add comments on user's posts.

    public function addPostComments(Request $request)
    {
        if ($request->method() == 'POST') {
            try {
                $validator = Validator::make($request->all(), [
                    'post_id' => 'required',
                    'commented_by' => 'required',
                    // 'commented_on' => 'required',
                    'comment' => 'required',
                ]);
                if ($validator->fails()) {
                    return response()->json(['status_code' => 1, 'message' => $validator->errors()->first()], 406);
                }
                $postcomment = PostComments::create([
                    'post_id' => $request->get('post_id'),
                    'commented_by' => $request->get('commented_by'),
                    'commented_on' => 0, //$request->get('commented_on'),
                    'comment' => $request->get('comment'),
                    'nested_comments' => '0',
                ]);

                if ($postcomment) {

                    // get inserted id 
                    $insertedId = $postcomment->id;
                    if ($request->post_comment_id == '0') {
                        return response()->json(['status_code' => 0, 'message' => 'Comments has been Added.'], 200);
                    } else {
                        $post = DB::table('post_comments')
                            ->select('nested_comments')
                            ->where('post_comment_id', $request->post_comment_id)
                            ->get()->toArray();

                        $currentData = $post[0]->nested_comments;
                        $post = DB::table('post_comments')
                            ->where('post_comment_id', $request->post_comment_id)
                            ->update(
                                [
                                    'nested_comments' => $currentData . ',' . $insertedId
                                ]
                            );

                        if ($postcomment) {

                            $postcomment_by_name = DB::table('user_detail')
                                ->where('user_id', $request->get('commented_by'))
                                ->select('name')
                                ->get();

                            $noti = new Notifications();
                            $tokens = DB::table('user_detail')
                                ->where('user_id', $request->get('commented_by'))
                                ->select('fcm_token')
                                ->get();
                            $noti_array = [
                                'title' => "Post Comments",
                                'body' => $postcomment_by_name[0]->name . " has commented on your post.",
                                'tokens' => $tokens[0]->fcm_token,
                            ];
                            $insertedId = $postcomment->id;
                            $notification = Notification::create([
                                'followers_id' => '0',
                                'post_id' => $insertedId,
                                'user_id' => $request->get('commented_by'),
                                'post_rate_id' => '0',
                                'post_comment_id' =>  $request->get('post_comment_id'),
                                'post_report_id' => '0',
                                'status' => '1',
                                'title' => 'post  is commented',
                                'type' => 'comment',
                                // 'message'=> $noti_array,
                                // 'message'=>  $noti_array = ['body'] ,
                                'message' => $noti_array['body'],
                                'is_viewed' => '0', //notification only sent not viewed.
                            ]);
                            if ($notification) {
                                $notify_status = 0;

                                $notify = DB::table('user_detail')
                                    ->select('notify_status')
                                    ->where('user_id', $request->get('commented_by'))
                                    ->get()->toArray();

                                if ($notify[0]->notify_status == 0) {
                                    $status = $noti->sendNotification($noti_array);
                                    if ($status == 1) {
                                        return response()->json(['status_code' => 0, 'message' => 'Comment have been added.'], 200);
                                    } else {
                                        return response()->json(['status_code' => 0, 'message' => 'Comment have been added..'], 200);
                                    }
                                } else {
                                    $notify_status = 1;
                                }
                            } else {
                                return response()->json(['status_code' => 0, 'message' => 'Comment have been added...'], 200);
                            }
                        } else {
                            return response()->json(['status_code' => 1, 'message' => 'Oops! Something went wrong.'], 200);
                        }
                        return response()->json(['status_code' => 0, 'message' => 'Comments has been Added.'], 200);
                    }
                } else {
                    return response()->json(['status_code' => 1, 'message' => 'Oops! Something went wrong.'], 200);
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method is not allowed'], 405);
        }
    }
}
