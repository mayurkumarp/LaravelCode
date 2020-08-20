<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\PostReports;
use DB;
use App\Notification;
use App\Http\Controllers\APINotificationController as Notifications;

class APIPostReportsController extends Controller
{
    // Use this function for Report user's Posts.
    
    public function addReports(Request $request)
    {
        if ($request->method() == 'POST') {
            try {
                $validator = Validator::make($request->all(), [
                    'post_id' => 'required',
                    'reported_by' => 'required',
                    'message' => 'required',
                ]);
                if ($validator->fails()) {
                    return response()->json(['status_code' => 1, 'message' => $validator->errors()->first()], 406);
                }
                $report = PostReports::create([
                    'post_id' => $request->get('post_id'),
                    'reported_by' => $request->get('reported_by'),
                    'message' => $request->get('message'),
                ]);
                if ($report) {
                    $report_by_name = DB::table('user_detail')
                        ->where('user_id', $request->get('reported_by'))
                        ->select('name')
                        ->get();
                    $noti = new Notifications();
                    $tokens = DB::table('user_detail')
                        ->select('fcm_token')
                        ->get();

                    $insertedId = $report->id;
                    $notification_counter = 0;
                    foreach ($tokens as $key => $value) {
                        $noti_array = [
                            'title' => "Post has been reported",
                            'body' => $report_by_name[0]->name . " has reported on post.",
                            'tokens' => $value->fcm_token,
                        ];

                        $notification = Notification::create([
                            'post_report_id' => $insertedId,
                            'post_id' => $request->get('post_id'),
                            'user_id' => $request->get('reported_by'),
                            'post_rate_id' => '0',
                            'post_comment_id' => '0',
                            'followers_id' => '0',
                            'status' => '1',
                            'message' => $request->get('message'),
                            'title' => $noti_array['title'],
                            'type' => 'Report',
                            'message' => $noti_array['body'],
                            'is_viewed' => '0',

                        ]);

                        if ($notification) {
                            $notify = DB::table('user_detail')
                                ->select('notify_status')
                                ->where('user_id', $request->get('reported_by'))
                                ->get()->toArray();
                            // if ($notify[0]->notify_status == 0) {
                            //     $status = $noti->sendNotification($noti_array);
                            //     if ($status == 1) {
                            //         $notification_counter++;
                            //     } else {
                            //         $notification_counter++;
                            //     }
                            // }
                        }
                    }
                    if (count($tokens) == $notification_counter) {
                        return response()->json(['status_code' => 0, 'message' => 'You have been Reported this post.'], 200);
                    } else {
                        return response()->json(['status_code' => 0, 'message' => 'You have been Reported this post..'], 200);
                    }
                    return response()->json(['status_code' => 0, 'message' => 'Post has been Reported.'], 200);
                } else {
                    return response()->json(['status_code' => 1, 'message' => 'Opps! Something went wrong.'], 200);
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method is not allowed'], 405);
        }
    }
}
