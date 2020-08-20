<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\PostRates;
use DB;
use App\Notification;
use App\Http\Controllers\APINotificationController as Notifications;

class APIPostRatesController extends Controller
{
    // Use this function for displaying all the rating on the posts.
    
    public function rateList(Request $request, $id)
    {
        if ($request->method() == 'GET') {
            try {
                if (!is_numeric($id)) {
                    return response()->json(['status_code' => 1, 'message' => 'Id must be integer value.'], 406);
                }
                $rate = DB::table('post_rates')
                    ->where('post_id', $id)
                    ->orderBy('created_at', 'desc')
                    ->get()->toArray();

                if ($rate) {
                    return response()->json(['status_code' => 0, 'message' => 'Rates Found.', 'results' => $rate], 200);
                } else {
                    return response()->json(['status_code' => 1, 'message' => 'Rates Not Found.'], 200);
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method is not allowed'], 405);
        }
    }

    // Use this function for remove rates on user's posts.

    public function deleteRate(Request $request, $id)
    {
        if ($request->method() == 'GET') {
            try {
                $postrate = DB::table('post_rates')
                    ->where('post_rate_id', $id)
                    ->delete();
                if ($postrate !== false) {
                    return response()->json(['status_code' => 0, 'message' => 'Rate has been Removed.'], 200);
                } else {
                    return response()->json(['status_code' => 1, 'message' => 'Opps! Something went wrong.'], 200);
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method not allowed.'], 405);
        }
    }

    // Use this function for Add Rates on user's Posts.

    public function addRates(Request $request)
    {
        if ($request->method() == 'POST') {
            try {
                $validator = Validator::make($request->all(), [
                    'post_id' => 'required',
                    'rated_by' => 'required',
                    'rated_to' => 'required',
                    'rate' => 'required',
                ]);
                if ($validator->fails()) {
                    return response()->json(['status_code' => 1, 'message' => $validator->errors()->first()], 406);
                }
                $rate = PostRates::create([
                    'post_id' => $request->get('post_id'),
                    'rated_by' => $request->get('rated_by'),
                    'rated_to' => $request->get('rated_to'),
                    'rate' => $request->get('rate'),
                ]);

                if ($rate) {
                    $postrated_by_name = DB::table('user_detail')
                        ->where('user_id', $request->get('rated_by'))
                        ->select('name')
                        ->get();

                    $noti = new Notifications();
                    $tokens = DB::table('user_detail')
                        ->where('user_id', $request->get('rated_to'))
                        ->select('fcm_token')
                        ->get();
                    $noti_array = [
                        'title' => "Post Rating",
                        'body' => $postrated_by_name[0]->name . " has rated on your post.",
                        'tokens' => $tokens[0]->fcm_token,
                    ];
                    $insertedId = $rate->id;

                    $notification = Notification::create([
                        'followers_id' => '0',
                        'post_id' => $insertedId,
                        'user_id' => $request->get('rated_by'),
                        'post_rate_id' => $request->get('rated_to'),
                        'post_comment_id' => '0',
                        'post_report_id' => '0',
                        'status' => '1',
                        'title' => 'post  is rated',
                        'type' => 'rate',
                        'message' => $noti_array['body'],
                        'is_viewed' => '0', //notification only sent not viewed.
                    ]);

                    if ($notification) {
                        $notify_status = 0;

                        $notify = DB::table('user_detail')
                            ->select('notify_status')
                            ->where('user_id', $request->get('rated_to'))
                            ->get()->toArray();

                        if ($notify[0]->notify_status == 0) {
                            $status = $noti->sendNotification($noti_array);
                            if ($status == 1) {
                                return response()->json(['status_code' => 0, 'message' => 'You have rated on post.'], 200);
                            } else {
                                return response()->json(['status_code' => 0, 'message' => 'You have rated on post..'], 200);
                            }
                        } else {
                            $notify_status = 1;
                        }
                    }
                    return response()->json(['status_code' => 0, 'message' => 'You have been rated on post.'], 200);
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
