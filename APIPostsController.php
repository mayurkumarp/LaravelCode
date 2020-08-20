<?php

namespace App\Http\Controllers;

use App\Posts;
use Illuminate\Http\Request;
use Validator;
use DB;
use App\Http\Controllers\APINotificationController as Notification;
use Illuminate\Support\Carbon;
// use Pawlox\VideoThumbnail\Facade\VideoThumbnail as VideoThumbnail;
// use Intervention\Image\Facades\Image as Image;
// use PHP_FFMPEG;

class APIPostsController extends Controller
{

    // Use this function for search perticular post.
    public function searchPost(Request $request)
    {
        if ($request->method() == 'POST') {
            try {
                $validator = Validator::make($request->all(), [
                    'user_id' => 'required|numeric',
                    'title' => 'required|string',
                    'type' => 'required|string',
                ]);

                if ($validator->fails()) {
                    return response()->json(['status_code' => 1, 'message' => $validator->errors()->first()], 406);
                }
                // $chat = DB::table('chat')
                //     ->where('chat_type', 'state')
                //     ->get()->toArray();

                // if ($chat) {
                //     return response()->json(['status_code' => 0, 'message' => 'State Chat Found.', 'results' => $chat], 404);
                // } else {
                //     return response()->json(['status_code' => 1, 'message' => 'State Chat Not Found.'], 404);
                // }

                //KR-//
                $data = [];
                // $post = DB::table('posts')
                //     ->where('type', "state")
                //     ->orderBy('created_at', 'desc')
                //     ->get()->toArray();
                // DB::enableQueryLog();

                $id = $request->get('user_id');
                $title = $request->get('title');
                $type = $request->get('type');

                $post = DB::table('posts')
                    ->whereRaw("posts.title Like '%$title%'")
                    ->where("posts.type", $request->get("type"))
                    ->orderBy('created_at', 'desc')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw('post_id'))
                            ->from('post_reports')
                            ->whereRaw('post_reports.post_id = posts.post_id');
                    })
                    ->get()->toArray();
                // dd(DB::getQueryLog());
                if ($post) {
                    $i = 0;
                    foreach ($post as $key => $value) {

                        if ($post) {

                            $count_likes = DB::table('post_likes')
                                ->where('post_id', $value->post_id)
                                ->count();

                            $count_comments = DB::table('post_comments')
                                ->where('post_id', $value->post_id)
                                ->count();

                            $is_like = DB::table('post_likes')
                                ->where('post_id', $value->post_id)
                                // ->where('liked_on', $value->post_id)
                                ->where('liked_by', $id)
                                ->count();
                            $is_follow = DB::table('followers')
                                ->where('followed_to', $value->user_id)
                                ->where('followed_by', $id)
                                ->count();

                            $user = DB::table('user_detail')
                                ->where('user_id', $value->user_id)
                                ->get()->toArray();

                            if ($user) {

                                $data[$i]['post_id'] = $value->post_id;
                                $data[$i]['user_id'] = $value->user_id;
                                $data[$i]['post_title'] = $value->title;
                                $data[$i]['status'] = $value->status;
                                $data[$i]['post_images'] = $value->image; //  need to convert into array
                                $data[$i]['file_type'] = $value->file_type; // file_type image or video in small
                                $data[$i]['post_type'] = $value->type; // type world or state
                                $data[$i]['promot_time'] = $value->promot_time;
                                $data[$i]['created_at'] = $value->created_at;
                                $data[$i]['name'] = $user[0]->name;
                                $data[$i]['profile_picture'] = $user[0]->profile_picture;
                                $data[$i]['count_likes'] = $count_likes;
                                $data[$i]['count_comments'] = $count_comments;
                                $data[$i]['is_like'] = $is_like;
                                $data[$i]['is_follow'] = $is_follow;

                                $i++;
                            }
                        } else {
                            return response()->json(['status_code' => 1, 'message' => 'Post Detail Not Found.'], 200);
                        }
                    } // for loop

                    if ($data) {
                        return response()->json(['status_code' => 0, 'message' => 'Post Detail Found.', 'results' => $data], 200);
                    } else {
                        return response()->json(['status_code' => 1, 'message' => 'Post Detail Not Found.'], 200);
                    }
                } else {
                    return response()->json(['status_code' => 1, 'message' => 'Post Detail Not Found.'], 200);
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method is not allowed'], 405);
        }
    }

    // Use this function for display perticular post's details.

    public function getPostDetails(Request $request, $post_id, $user_id)
    {
        if ($request->method() == 'GET') {
            try {
                if (!is_numeric($post_id) or !is_numeric($user_id)) {
                    return response()->json(['status_code' => 1, 'message' => 'Id must be integer value'], 406);
                }
                // if (!is_numeric($user_id)) {
                //     return response()->json(['status_code' => 1, 'message' => 'Id must be integer value'], 406);
                // }
                $data = [];
                $post = DB::table('posts')
                    ->where('post_id', $post_id)
                    // ->where('user_id', $user_id)
                    ->orderBy('created_at', 'desc')
                    ->get()->toArray();

                if ($post) {

                    $count_likes = DB::table('post_likes')
                        ->where('post_id', $post_id)
                        ->count();

                    $count_comments = DB::table('post_comments')
                        ->where('post_id', $post_id)
                        ->count();

                    $comment_list = DB::table('post_comments')
                        ->select('post_comments.*', 'user_detail.name as commented_username', 'user_detail.profile_picture as commented_userprofile')
                        ->where('post_id', $post_id)
                        ->orderBy('created_at', 'desc')
                        ->leftjoin('user_detail', function ($join) {
                            $join->on('post_comments.commented_by', '=', 'user_detail.user_id');
                        })
                        ->get()->toArray();

                    $avg_rates = DB::table('post_rates')
                        ->where('post_id', $post_id)
                        ->avg('rate');

                    $avg_rates = $avg_rates === null ? 0 : round($avg_rates, 0, PHP_ROUND_HALF_UP);

                    $is_like = DB::table('post_likes')
                        ->where('post_id', $post_id)
                        // ->where('liked_on', $post_id)
                        ->where('liked_by', $user_id)
                        ->count();
                    $is_follow = DB::table('followers')
                        ->where('followed_to', $post[0]->user_id)
                        ->where('followed_by', $user_id)
                        ->count();

                    $is_rated = DB::table('post_rates')
                        ->where('post_id', $post_id)
                        ->where('rated_by', $user_id)
                        ->count();

                    $is_rated = $is_rated === 0 ? 0 : 1;

                    // $user = DB::table('user_detail')
                    //     ->where('user_id', $value->user_id)
                    //     ->get()->toArray();

                    // if ($user) {

                    $data['post_id'] = $post[0]->post_id;
                    $data['user_id'] = $post[0]->user_id;
                    $data['title'] = $post[0]->title;
                    $data['status'] = $post[0]->status;
                    $data['images'] = $post[0]->image; //  need to convert into array
                    $data['file_type'] = $post[0]->file_type; // file_type image or video in small
                    $data['post_type'] = $post[0]->type; // type world or state
                    $data['promot_time'] = $post[0]->promot_time;
                    $data['created_at'] = $post[0]->created_at;
                    $data['count_likes'] = $count_likes;
                    $data['count_comments'] = $count_comments;
                    $data['avg_rates'] = $avg_rates;
                    $data['is_like'] = $is_like;
                    $data['is_follow'] = $is_follow;
                    $data['is_rated'] = $is_rated;
                    $data['comment_list'] = $comment_list;

                    if ($data) {
                        return response()->json(['status_code' => 0, 'message' => 'Post Details Found.', 'results' => $data], 200);
                    } else {
                        return response()->json(['status_code' => 1, 'message' => 'Post Details Not Found.'], 200);
                    }
                } else {
                    return response()->json(['status_code' => 1, 'message' => 'Post Details Not Found.'], 200);
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method is not allowed'], 405);
        }
    }

    // Use this function for remove post image.

    public function deletePostImage(Request $request)
    {
        if ($request->method() == 'POST') {
            try {

                $validator = Validator::make($request->all(), [
                    // 'image' => 'required',
                ]);

                if ($validator->fails()) {
                    return response()->json(['status_code' => 1, 'message' => $validator->errors()->first()], 406);
                }
                $post = DB::table('posts')

                    ->select('image')
                    ->where('post_id', $request->post_id)
                    ->get();

                $imagepatharray = explode(',', $post[0]->image);

                if (($key = array_search($request->image, $imagepatharray)) !== false) {
                    unset($imagepatharray[$key]);
                }
                $imagepath = implode(',', $imagepatharray);
                $post = DB::table('posts')
                    ->where('post_id', $request->post_id)
                    ->update(
                        [
                            'image' => $imagepath,
                        ]
                    );
                if ($post) {
                    return response()->json(['status_code' => 0, 'message' => 'Post has been deleted.', 'results' => $post], 200);
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

    // Use this function for delete user's posts.

    public function deletePost(Request $request, $post_id)
    {
        if ($request->method() == 'GET') {
            try {
                $post =  Posts::destroy($post_id);

                if ($post) {
                    return response()->json(['status_code' => 0, 'message' => 'Post has been deleted.'], 200);
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

    // Use this function for display all added user's posts.

    public function postDetailsList(Request $request, $id)
    {
        if ($request->method() == 'GET') {
            try {
                $post = DB::table('posts')
                    ->select('posts.post_id', 'title', 'type', 'image', 'file_type', 'name', 'profile_picture', 'posts.created_at as created_at')
                    // ->where('posts.user_id', $id)
                    ->orderBy('posts.created_at', 'desc')
                    ->leftJoin('user_detail', 'user_detail.user_id', '=', 'posts.user_id')
                    ->get()->toArray();

                if ($post) {

                    return response()->json(['status_code' => 0, 'message' => 'Post Found.', 'results' => $post], 200);
                } else {

                    return response()->json(['status_code' => 1, 'message' => 'Post Not Found.'], 200);
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method is not allowed'], 405);
        }
    }

    // Use this function for edit existing post of user's.

    public function editPostDetails(Request $request)
    {
        if ($request->method() == 'POST') {
            try {
                $validator = Validator::make($request->all(), [
                    'post_id' => 'required',
                    'user_id' => 'required',
                    'title' => 'required',
                    'type' => 'required',
                    'image' => 'required',

                ]);

                if ($validator->fails()) {
                    return response()->json(['status_code' => 1, 'message' => $validator->errors()->first()], 406);
                }
                // if (count($request->image) > 5) {
                //     return response()->json(['status_code' => 1, 'message' => 'Please Choose only 5 or less Images.'], 404);
                // }
                if ($request->hasFile('image')) {
                    $store_name = "";
                    $cnt = 1;
                    foreach ($request->allFiles('image') as $file) {

                        $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." . $file->getClientOriginalExtension());
                        $file->move(public_path() . '/uploads/posts/', $imageName);
                        // $store_name = $store_name . ',' . '/uploads/posts/' . $imageName;

                        if ($cnt != 1) {
                            $store_name = $store_name . "," . '/uploads/posts/' . $imageName;
                        } else {
                            $store_name = $store_name . '/uploads/posts/' . $imageName;
                        }
                        $cnt++;
                    }
                    // DB::enableQueryLog();
                    $check = DB::table('posts')
                        ->where('post_id', $request->post_id)
                        ->where('user_id', $request->user_id)
                        ->update(
                            [
                                'title' => $request->title,
                                'type' => $request->type,
                                'image' => $store_name,
                            ]
                        );
                    // dd(DB::getQueryLog());

                    if ($check) {
                        return response()->json(['status_code' => 0, 'message' => 'Your Post has been Successfully Updated.'], 200);
                    } else {
                        return response()->json(['status_code' => 1, 'message' => 'Oops! Something went wrong.'], 200);
                    }
                } else {
                    return response()->json(['status_code' => 1, 'message' => 'Please Choose Image/Video File.'], 200);
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method not allowed.'], 405);
        }
    }

    // OLD 18-03
    // public function addPost(Request $request)
    // {
    //     if ($request->method() == 'POST') {
    //         try {
    //             // $messages = array(
    //             // "upload_count.max" => 'The :attribute field cannot be more than 3.',
    //             // );
    //             $validator = Validator::make($request->all(), [
    //                 'user_id' => 'required',
    //                 'title' => 'required',
    //                 'type' => 'required',
    //                 'image' => 'required',
    //                 // 'upload_count' => 'max:3',
    //                 // array('type' => array('upload_count:type,3')),
    //                 // // $messages
    //             ]);
    //             if ($validator->fails()) {
    //                 return response()->json(['status_code' => 1, 'message' => $validator->errors()->first()], 406);
    //             }
    //             // if (count($request->image) > 5) {
    //             //     return response()->json(['status_code' => 1, 'message' => 'For a single post, Only 5 or less image/video are allowed.'], 404);
    //             // }
    //             // KR-//
    //             $store_name = "";
    //             // -KR//

    //             if ($request->file('image')) {

    //                 $file = $request->file('image');
    //                 $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." . $file->getClientOriginalExtension());
    //                 $file->move(public_path() . '/uploads/posts/', $imageName);
    //                 $store_name = '/uploads/posts/' . $imageName;

    //                 $store_name_thumbnail = null;
    //                 // if ($request->file('thumbnail')) {
    //                 //     $file_thumbnail = $request->file('thumbnail');
    //                 //     $imageName_thumbnail = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." . $file_thumbnail->getClientOriginalExtension());
    //                 //     $file_thumbnail->move(public_path() . '/uploads/posts/thumbnail/', $imageName_thumbnail);
    //                 //     $store_name_thumbnail = '/uploads/posts/thumbnail/' . $imageName_thumbnail;
    //                 // }

    //                 //KR-//
    //                 // $cnt = 1;
    //                 // foreach ($request->file('image') as $file) {
    //                 // $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." .$file->getClientOriginalExtension());
    //                 // $file->move(public_path() . '/uploads/posts/', $imageName);
    //                 // if($cnt != 1)
    //                 // {
    //                 // $store_name = $store_name . "," . '/uploads/posts/' . $imageName;
    //                 // }
    //                 // else
    //                 // {
    //                 // $store_name = $store_name . '/uploads/posts/' . $imageName;
    //                 // }
    //                 // $cnt++;
    //                 // }
    //                 //-KR//

    //                 if ($request->get('type') === "state") {
    //                     $promot_time = 12;
    //                 } else if ($request->get('type') === "world") {
    //                     $promot_time = 24;
    //                 } else {
    //                     $promot_time = '';
    //                 }

    //                 $post = Posts::create([
    //                     'user_id' => $request->get('user_id'),
    //                     'title' => $request->get('title'),
    //                     'status' => '1',
    //                     'promot_time' => $promot_time,
    //                     'type' => $request->get('type'),
    //                     'image' => $store_name,
    //                     'file_type' => 'image', // because it is image (DO not change it.)
    //                     'thumbnail' => '',
    //                 ]);
    //                 if ($post) {

    //                     $followers = DB::table('followers')
    //                         ->where('followed_to', $request->get('user_id'))
    //                         ->where('is_followed', '1')
    //                         ->join('user_detail', 'user_detail.user_id', '=', 'followers.followed_by')
    //                         ->select('user_detail.fcm_token')
    //                         ->get();

    //                     if (count($followers) > 0) {
    //                         $noti = new Notification();
    //                         $tokens = array();
    //                         foreach ($followers as $key => $value) {
    //                             array_push($tokens, $value->fcm_token);
    //                         }

    //                         $noti_array = [
    //                             'title' => "New Post",
    //                             'body' => "New Post is added.",
    //                             'tokens' => $tokens,
    //                         ];

    //                         $status = $noti->sendNotification($noti_array);
    //                         if ($status == 1) {
    //                             return response()->json(['status_code' => 0, 'message' => 'Post Added Successfully.'], 200);
    //                         } else {
    //                             return response()->json(['status_code' => 0, 'message' => 'Post Added Successfully..'], 200);
    //                         }
    //                     } else {
    //                         return response()->json(['status_code' => 0, 'message' => 'Post Added Successfully...'], 200);
    //                     }
    //                 } else {
    //                     return response()->json(['status_code' => 1, 'message' => 'Oops! Something went wrong.'], 200);
    //                 }
    //             } else {
    //                 return response()->json(['status_code' => 1, 'message' => 'Error in File Format.'], 200);
    //             }
    //         } catch (Exception $ex) {
    //             return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
    //         }
    //     } else {
    //         return response()->json(['status_code' => 1, 'message' => 'Method is not allowed'], 405);
    //     }
    // }

    // Use this function for add user's posts.

    public function addPost(Request $request)
    {
        if ($request->method() == 'POST') {
            try {

                $validator = Validator::make($request->all(), [
                    'user_id' => 'required',
                    'title' => 'required',
                    'type' => 'required',
                    'image' => 'required',

                ]);
                if ($validator->fails()) {
                    return response()->json(['status_code' => 1, 'message' => $validator->errors()->first()], 406);
                }

                if ($request->get('type')  == 'following') {

                    $store_name = "";
                    $store_name_thumbnail = "/uploads/posts/thumbnail/default-image.png";
                    if ($request->file('image')) {

                        $file = $request->file('image');
                        $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." . $file->getClientOriginalExtension());
                        $file->move(public_path() . '/uploads/posts/', $imageName);
                        $store_name = '/uploads/posts/' . $imageName;

                        //    // KR-//
                        //    $cnt = 1;
                        //    foreach ($request->file('image') as $file) {
                        //        $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." . $file->getClientOriginalExtension());
                        //        $file->move(public_path() . '/uploads/posts/', $imageName);
                        //        if ($cnt != 1) {
                        //            $store_name = $store_name . "," . '/uploads/posts/' . $imageName;
                        //        } else {
                        //            $store_name = $store_name . '/uploads/posts/' . $imageName;
                        //        }
                        //        $cnt++;
                        //    }
                        //    // -KR//

                        if ($request->get('type') === "state") {
                            $promot_time = 12;
                        } else if ($request->get('type') === "world") {
                            $promot_time = 24;
                        } else {
                            $promot_time = '';
                        }

                        $post = Posts::create([
                            'user_id' => $request->get('user_id'),
                            'title' => $request->get('title'),
                            'status' => '1',
                            'promot_time' => $promot_time,
                            'type' => $request->get('type'),
                            'image' => $store_name,
                            'file_type' => 'image', // because it is image (DO not change it.)
                            'thumbnail' => $store_name_thumbnail,
                            'created_at' => $request->get('created_at'),
                        ]);
                        if ($post) {

                            $followers = DB::table('followers')
                                ->where('followed_to', $request->get('user_id'))
                                ->where('is_followed', '1')
                                ->join('user_detail', 'user_detail.user_id', '=', 'followers.followed_by')
                                ->select('user_detail.fcm_token')
                                ->get();

                            if (count($followers) > 0) {
                                $noti = new Notification();
                                $tokens = array();
                                foreach ($followers as $key => $value) {
                                    array_push($tokens, $value->fcm_token);
                                }

                                $noti_array = [
                                    'title' => "New Post",
                                    'body' => "New Post is added.",
                                    'tokens' => $tokens,
                                ];
                                $status = $noti->sendNotification($noti_array);
                                if ($status == 1) {
                                    return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully.'], 200);
                                } else {
                                    return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                }
                            } else {
                                return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                            }
                        } else {
                            return response()->json(['status_code' => 1, 'message' => 'Somethong went wrong.'], 200);
                        }
                    } else {
                        return response()->json(['status_code' => 1, 'message' => 'Error in Image Format.'], 200);
                    }
                } else {
                    $lastPostCount = DB::table('posts')
                        ->select('created_at', 'type')
                        ->where('type', $request->get('type'))
                        ->where('user_id', $request->get('user_id'))
                        ->count();

                    if ($lastPostCount >= 1) {
                        $lastPost = DB::table('posts')
                            ->select('created_at', 'type')
                            ->where('type', $request->get('type'))
                            ->where('user_id', $request->get('user_id'))
                            ->get()->toArray();
                            // dd('type', $request->get('type'));
                        if ($lastPost[0]->type === "state") {
                            $stateLastPost = DB::table('posts')
                                ->where('user_id', $request->get('user_id'))
                                ->orderBy('created_at', 'desc')
                                ->limit(1)
                                ->where('posts.created_at', '>', Carbon::now()->subHours(12)->toDateTimeString())
                                ->get()->toArray();
                            if ($stateLastPost == true) {
                                return response()->json(['status_code' => 1, 'message' => 'You can`t add post in this type, See your last post timers.'], 200);
                            } else {
                                $store_name = "";
                                $store_name_thumbnail = "/uploads/posts/thumbnail/default-image.png";
                                if ($request->file('image')) {
                                    $file = $request->file('image');
                                    $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." . $file->getClientOriginalExtension());
                                    $file->move(public_path() . '/uploads/posts/', $imageName);
                                    $store_name = '/uploads/posts/' . $imageName;

                                    // // KR-//
                                    // $cnt = 1;
                                    // foreach ($request->file('image') as $file) {
                                    //     $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." . $file->getClientOriginalExtension());
                                    //     $file->move(public_path() . '/uploads/posts/', $imageName);
                                    //     if ($cnt != 1) {
                                    //         $store_name = $store_name . "," . '/uploads/posts/' . $imageName;
                                    //     } else {
                                    //         $store_name = $store_name . '/uploads/posts/' . $imageName;
                                    //     }
                                    //     $cnt++;
                                    // }
                                    // // -KR//

                                    if ($request->get('type') === "state") {
                                        $promot_time = 12;
                                    } else if ($request->get('type') === "world") {
                                        $promot_time = 24;
                                    } else {
                                        $promot_time = '';
                                    }

                                    $post = Posts::create([
                                        'user_id' => $request->get('user_id'),
                                        'title' => $request->get('title'),
                                        'status' => '1',
                                        'promot_time' => $promot_time,
                                        'type' => $request->get('type'),
                                        'image' => $store_name,
                                        'file_type' => 'image', // because it is image (DO not change it.)
                                        'thumbnail' => $store_name_thumbnail,
                                        'created_at' => $request->get('created_at'),
                                    ]);

                                    if ($post) {

                                        $followers = DB::table('followers')
                                            ->where('followed_to', $request->get('user_id'))
                                            ->where('is_followed', '1')
                                            ->join('user_detail', 'user_detail.user_id', '=', 'followers.followed_by')
                                            ->select('user_detail.fcm_token')
                                            ->get();

                                        if (count($followers) > 0) {
                                            $noti = new Notification();
                                            $tokens = array();
                                            foreach ($followers as $key => $value) {
                                                array_push($tokens, $value->fcm_token);
                                            }

                                            $noti_array = [
                                                'title' => "New Post",
                                                'body' => "New Post is added.",
                                                'tokens' => $tokens,
                                            ];
                                            $status = $noti->sendNotification($noti_array);
                                            if ($status == 1) {
                                                return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully.'], 200);
                                            } else {
                                                return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                            }
                                        } else {
                                            return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                        }
                                    } else {
                                        return response()->json(['status_code' => 1, 'message' => 'Somethong went wrong.'], 200);
                                    }
                                } else {
                                    return response()->json(['status_code' => 1, 'message' => 'Error in Image Format.'], 200);
                                }
                            }
                        } else if ($lastPost[0]->type === "world") {
                            $worldLastPost = DB::table('posts')
                                ->where('user_id', $request->get('user_id'))
                                ->orderBy('created_at', 'desc')
                                ->limit(1)
                                ->where('posts.created_at', '>', Carbon::now()->subHours(24)->toDateTimeString())
                                ->get()->toArray();
                            if ($worldLastPost == true) {
                                return response()->json(['status_code' => 1, 'message' => 'You can`t add post in this type, See your last post timers.'], 200);
                            } else {
                                $store_name = "";
                                $store_name_thumbnail = "/uploads/posts/thumbnail/default-image.png";
                                if ($request->file('image')) {
                                    $file = $request->file('image');
                                    $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." . $file->getClientOriginalExtension());
                                    $file->move(public_path() . '/uploads/posts/', $imageName);
                                    $store_name = '/uploads/posts/' . $imageName;

                                    // // KR-//
                                    // $cnt = 1;
                                    // foreach ($request->file('image') as $file) {
                                    //     $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." . $file->getClientOriginalExtension());
                                    //     $file->move(public_path() . '/uploads/posts/', $imageName);
                                    //     if ($cnt != 1) {
                                    //         $store_name = $store_name . "," . '/uploads/posts/' . $imageName;
                                    //     } else {
                                    //         $store_name = $store_name . '/uploads/posts/' . $imageName;
                                    //     }
                                    //     $cnt++;
                                    // }
                                    // // -KR//

                                    if ($request->get('type') === "state") {
                                        $promot_time = 12;
                                    } else if ($request->get('type') === "world") {
                                        $promot_time = 24;
                                    } else {
                                        $promot_time = '';
                                    }

                                    $post = Posts::create([
                                        'user_id' => $request->get('user_id'),
                                        'title' => $request->get('title'),
                                        'status' => '1',
                                        'promot_time' => $promot_time,
                                        'type' => $request->get('type'),
                                        'image' => $store_name,
                                        'file_type' => 'image', // because it is image (DO not change it.)
                                        'thumbnail' =>$store_name_thumbnail,
                                        'created_at' => $request->get('created_at'),
                                    ]);
                                    if ($post) {

                                        $followers = DB::table('followers')
                                            ->where('followed_to', $request->get('user_id'))
                                            ->where('is_followed', '1')
                                            ->join('user_detail', 'user_detail.user_id', '=', 'followers.followed_by')
                                            ->select('user_detail.fcm_token')
                                            ->get();

                                        if (count($followers) > 0) {
                                            $noti = new Notification();
                                            $tokens = array();
                                            foreach ($followers as $key => $value) {
                                                array_push($tokens, $value->fcm_token);
                                            }

                                            $noti_array = [
                                                'title' => "New Post",
                                                'body' => "New Post is added.",
                                                'tokens' => $tokens,
                                            ];
                                            $status = $noti->sendNotification($noti_array);
                                            if ($status == 1) {
                                                return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully.'], 200);
                                            } else {
                                                return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                            }
                                        } else {
                                            return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                        }
                                    } else {
                                        return response()->json(['status_code' => 1, 'message' => 'Somethong went wrong.'], 200);
                                    }
                                } else {
                                    return response()->json(['status_code' => 1, 'message' => 'Error in Image Format.'], 200);
                                }
                            }
                        }
                    } else {

                        $store_name = "";
                        $store_name_thumbnail = "/uploads/posts/thumbnail/default-image.png";

                        if ($request->file('image')) {

                            $file = $request->file('image');
                            $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." . $file->getClientOriginalExtension());
                            $file->move(public_path() . '/uploads/posts/', $imageName);
                            $store_name = '/uploads/posts/' . $imageName;

                            //    // KR-//
                            //    $cnt = 1;
                            //    foreach ($request->file('image') as $file) {
                            //        $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." . $file->getClientOriginalExtension());
                            //        $file->move(public_path() . '/uploads/posts/', $imageName);
                            //        if ($cnt != 1) {
                            //            $store_name = $store_name . "," . '/uploads/posts/' . $imageName;
                            //        } else {
                            //            $store_name = $store_name . '/uploads/posts/' . $imageName;
                            //        }
                            //        $cnt++;
                            //    }
                            //    // -KR//

                            if ($request->get('type') === "state") {
                                $promot_time = 12;
                            } else if ($request->get('type') === "world") {
                                $promot_time = 24;
                            } else {
                                $promot_time = '';
                            }

                            $post = Posts::create([
                                'user_id' => $request->get('user_id'),
                                'title' => $request->get('title'),
                                'status' => '1',
                                'promot_time' => $promot_time,
                                'type' => $request->get('type'),
                                'image' => $store_name,
                                'file_type' => 'image', // because it is image (DO not change it.)
                                'thumbnail' => $store_name_thumbnail,
                                'created_at' => $request->get('created_at'),
                            ]);
                            if ($post) {

                                $followers = DB::table('followers')
                                    ->where('followed_to', $request->get('user_id'))
                                    ->where('is_followed', '1')
                                    ->join('user_detail', 'user_detail.user_id', '=', 'followers.followed_by')
                                    ->select('user_detail.fcm_token')
                                    ->get();

                                if (count($followers) > 0) {
                                    $noti = new Notification();
                                    $tokens = array();
                                    foreach ($followers as $key => $value) {
                                        array_push($tokens, $value->fcm_token);
                                    }

                                    $noti_array = [
                                        'title' => "New Post",
                                        'body' => "New Post is added.",
                                        'tokens' => $tokens,
                                    ];
                                    $status = $noti->sendNotification($noti_array);
                                    if ($status == 1) {
                                        return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully.'], 200);
                                    } else {
                                        return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                    }
                                } else {
                                    return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                }
                            } else {
                                return response()->json(['status_code' => 1, 'message' => 'Somethong went wrong.'], 200);
                            }
                        } else {
                            return response()->json(['status_code' => 1, 'message' => 'Error in Image Format.'], 200);
                        }
                    }
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method is not allowed'], 405);
        }
    }

    // Use this function for Add Video in the Posts.

    public function addPostWithVideo(Request $request)
    {
        if ($request->method() == 'POST') {
            try {
                // $messages = array(
                //     "upload_count.max" => 'The :attribute field cannot be more than 3.',
                // );
                $validator = Validator::make($request->all(), [
                    'user_id' => 'required',
                    'title' => 'required',
                    'type' => 'required',
                    'video' => 'required|file',
                    //'upload_count' => 'max:3',
                    // array('type' => array('upload_count:type,3')),
                    // $messages
                ]);
                if ($validator->fails()) {
                    return response()->json(['status_code' => 1, 'message' => $validator->errors()->first()], 406);
                }
                // if (count($request->image) > 5) {
                //     return response()->json(['status_code' => 1, 'message' => 'For a single post, Only 5 or less image/video are allowed.'], 404);
                // }
                // KR-//

                if ($request->get('type')  == 'following') {
                    $store_name = "";
                    $store_name_thumbnail = "/uploads/posts/thumbnail/default-video.png";
                    // -KR//

                    if ($request->file('video')) {

                        $file = $request->file('video');
                        $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . "VIDEO" . "_" . rand() . "." . $file->getClientOriginalExtension());
                        $file->move(public_path() . '/uploads/posts/', $imageName);
                        $store_name = '/uploads/posts/' . $imageName;

                        // $img = Image::make(public_path() . '/uploads/posts/' . $imageName)->resize(200, 200)->save(public_path() . '/uploads/posts/thumbnail/');

                        // dd($img);

                        ///////////////////////

                        // $thumbnail_path = public_path('/uploads/posts/thumbnail');
                        // $thumbName = str_replace(":", "_", date('Y-m-d_His') . "_" . "THUMBNAIL" . "." . rand() . ".png");
                        // $store_name_thumbnail = $thumbnail_path . '/' . $thumbName;

                        // $VideoThumbnail = '';
                        // VideoThumbnail::createThumbnail(storage_path('app/public/videos/movie.mp4'), storage_path('app/public/thumbs'), 'thumbnail.png', 3, 200, 200);

                        // VideoThumbnail::createThumbnail(public_path('uploads/posts/movie.mp4'), public_path('uploads/posts/thumbnail'), 'movie.jpg', 2, 1920, 1080);
                        // VideoThumbnail::createThumbnail(public_path() . $store_name, $thumbnail_path, $thumbName, 5, $width = 240, $height = 200);

                        ////////////////////////////

                        // $thumbnail_path = public_path() . 'uploads/posts/thumbnail';
                        // $file = $request->file('video');
                        // $thumbvideoPath  = public_path('uploads/posts/') . 'movie.mp4';
                        // // $video_path       = $destination_path . '/' . $file_name;
                        // $thumbnail_image  = "movie.jpg";

                        // dd($thumbvideoPath);
                        // $thumbnail_status = VideoThumbnail::createThumbnail($thumbvideoPath, $thumbnail_path, $thumbnail_image, 5);

                        // dd($thumbnail_status);
                        // if ($thumbnail_status) {
                        //     echo "Thumbnail generated";
                        // } else {
                        //     echo "thumbnail generation has failed";
                        // }

                        //////////////////////

                        // $thumbnail_path = storage_path() . '/app/public/thumbs';
                        // $file = $request->file('video');
                        // $thumbvideoPath  = storage_path('app/public/videos/') . 'movie.mp4';
                        // // $video_path       = $destination_path . '/' . $file_name;
                        // $thumbnail_image  = "movie.jpg";

                        // // $thumbnail_status = VideoThumbnail::createThumbnail($thumbvideoPath, $thumbnail_path, $thumbnail_image, 4);
                        // $thumbnail_status = VideoThumbnail::createThumbnail(storage_path('app/public/videos/movie.mp4'), storage_path('app/public/videos/thumbs'), '111.jpg', 2, 600, 600);

                        //KR-//
                        // $cnt = 1;
                        // foreach ($request->file('image') as $file) {
                        //     $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." .$file->getClientOriginalExtension());
                        //     $file->move(public_path() . '/uploads/posts/', $imageName);
                        //     if($cnt != 1)
                        //     {
                        //         $store_name = $store_name . "," . '/uploads/posts/' . $imageName;
                        //     }
                        //     else
                        //     {
                        //         $store_name = $store_name . '/uploads/posts/' . $imageName;
                        //     }
                        //     $cnt++;
                        // }
                        //-KR//

                        if ($request->get('type') === "state") {
                            $promot_time = 12;
                        } else if ($request->get('type') === "world") {
                            $promot_time = 24;
                        } else {
                            $promot_time = '';
                        }

                        $post = Posts::create([
                            'user_id' => $request->get('user_id'),
                            'title' => $request->get('title'),
                            'status' => '1',
                            'promot_time' => $promot_time,
                            'type' => $request->get('type'),
                            'image' => $store_name,
                            'file_type' => 'video', // because it is video (DO not change it.)
                            'thumbnail' => $store_name_thumbnail,
                        ]);
                        if ($post) {
                            $followers = DB::table('followers')
                                ->where('followed_to', $request->get('user_id'))
                                ->where('is_followed', '1')
                                ->join('user_detail', 'user_detail.user_id', '=', 'followers.followed_by')
                                ->select('user_detail.fcm_token')
                                ->get();

                            if (count($followers) > 0) {
                                $noti = new Notification();
                                $tokens = array();
                                foreach ($followers as $key => $value) {
                                    array_push($tokens, $value->fcm_token);
                                }

                                $noti_array = [
                                    'title' => "New Post",
                                    'body' => "New Post is added.",
                                    'tokens' => $tokens,
                                ];

                                $status = $noti->sendNotification($noti_array);
                                if ($status == 1) {
                                    return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully.'], 200);
                                } else {
                                    return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                }
                            } else {
                                return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                            }
                        } else {
                            return response()->json(['status_code' => 1, 'message' => 'Oops! Something went wrong.'], 200);
                        }
                    } else {
                        return response()->json(['status_code' => 1, 'message' => 'Error in File Format.'], 200);
                    }
                } else {
                    $lastPostCount = DB::table('posts')
                        ->select('created_at', 'type')
                        ->where('type', $request->get('type'))
                        ->where('user_id', $request->get('user_id'))
                        ->count();

                    if ($lastPostCount >= 1) {
                        $lastPost = DB::table('posts')
                            ->select('created_at', 'type')
                            ->where('type', $request->get('type'))
                            ->where('user_id', $request->get('user_id'))
                            ->get()->toArray();
                        if ($lastPost[0]->type === "state") {
                            $stateLastPost = DB::table('posts')
                                ->where('user_id', $request->get('user_id'))
                                ->orderBy('created_at', 'desc')
                                ->limit(1)
                                ->where('posts.created_at', '>', Carbon::now()->subHours(12)->toDateTimeString())
                                ->get()->toArray();
                            if ($stateLastPost == true) {
                                return response()->json(['status_code' => 1, 'message' => 'You can`t add post in this type, See your last post timers.'], 200);
                            } else {
                                $store_name = "";
                                $store_name_thumbnail = "/uploads/posts/thumbnail/default-video.png";
                                // -KR//

                                if ($request->file('video')) {

                                    $file = $request->file('video');
                                    $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . "VIDEO" . "_" . rand() . "." . $file->getClientOriginalExtension());
                                    $file->move(public_path() . '/uploads/posts/', $imageName);
                                    $store_name = '/uploads/posts/' . $imageName;

                                    //KR-//
                                    // $cnt = 1;
                                    // foreach ($request->file('image') as $file) {
                                    //     $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." .$file->getClientOriginalExtension());
                                    //     $file->move(public_path() . '/uploads/posts/', $imageName);
                                    //     if($cnt != 1)
                                    //     {
                                    //         $store_name = $store_name . "," . '/uploads/posts/' . $imageName;
                                    //     }
                                    //     else
                                    //     {
                                    //         $store_name = $store_name . '/uploads/posts/' . $imageName;
                                    //     }
                                    //     $cnt++;
                                    // }
                                    //-KR//

                                    if ($request->get('type') === "state") {
                                        $promot_time = 12;
                                    } else if ($request->get('type') === "world") {
                                        $promot_time = 24;
                                    } else {
                                        $promot_time = null;
                                    }

                                    $post = Posts::create([
                                        'user_id' => $request->get('user_id'),
                                        'title' => $request->get('title'),
                                        'status' => '1',
                                        'promot_time' => $promot_time,
                                        'type' => $request->get('type'),
                                        'image' => $store_name,
                                        'file_type' => 'video', // because it is video (DO not change it.)
                                        'thumbnail' => $store_name_thumbnail,
                                    ]);
                                    if ($post) {
                                        $followers = DB::table('followers')
                                            ->where('followed_to', $request->get('user_id'))
                                            ->where('is_followed', '1')
                                            ->join('user_detail', 'user_detail.user_id', '=', 'followers.followed_by')
                                            ->select('user_detail.fcm_token')
                                            ->get();

                                        if (count($followers) > 0) {
                                            $noti = new Notification();
                                            $tokens = array();
                                            foreach ($followers as $key => $value) {
                                                array_push($tokens, $value->fcm_token);
                                            }

                                            $noti_array = [
                                                'title' => "New Post",
                                                'body' => "New Post is added.",
                                                'tokens' => $tokens,
                                            ];

                                            $status = $noti->sendNotification($noti_array);
                                            if ($status == 1) {
                                                return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully.'], 200);
                                            } else {
                                                return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                            }
                                        } else {
                                            return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                        }
                                    } else {
                                        return response()->json(['status_code' => 1, 'message' => 'Oops! Something went wrong.'], 200);
                                    }
                                } else {
                                    return response()->json(['status_code' => 1, 'message' => 'Error in File Format.'], 200);
                                }
                            }
                        } else if ($lastPost[0]->type === "world") {
                            $worldLastPost = DB::table('posts')
                                ->where('user_id', $request->get('user_id'))
                                ->orderBy('created_at', 'desc')
                                ->limit(1)
                                ->where('posts.created_at', '>', Carbon::now()->subHours(24)->toDateTimeString())
                                ->get()->toArray();
                            if ($worldLastPost == true) {
                                return response()->json(['status_code' => 1, 'message' => 'You can`t add post in this type, See your last post timers.'], 200);
                            } else {
                                $store_name = "";
                                $store_name_thumbnail = "/uploads/posts/thumbnail/default-video.png";
                                // -KR//

                                if ($request->file('video')) {

                                    $file = $request->file('video');
                                    $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . "VIDEO" . "_" . rand() . "." . $file->getClientOriginalExtension());
                                    $file->move(public_path() . '/uploads/posts/', $imageName);
                                    $store_name = '/uploads/posts/' . $imageName;

                                    //KR-//
                                    // $cnt = 1;
                                    // foreach ($request->file('image') as $file) {
                                    //     $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." .$file->getClientOriginalExtension());
                                    //     $file->move(public_path() . '/uploads/posts/', $imageName);
                                    //     if($cnt != 1)
                                    //     {
                                    //         $store_name = $store_name . "," . '/uploads/posts/' . $imageName;
                                    //     }
                                    //     else
                                    //     {
                                    //         $store_name = $store_name . '/uploads/posts/' . $imageName;
                                    //     }
                                    //     $cnt++;
                                    // }
                                    //-KR//

                                    if ($request->get('type') === "state") {
                                        $promot_time = 12;
                                    } else if ($request->get('type') === "world") {
                                        $promot_time = 24;
                                    } else {
                                        $promot_time = null;
                                    }

                                    $post = Posts::create([
                                        'user_id' => $request->get('user_id'),
                                        'title' => $request->get('title'),
                                        'status' => '1',
                                        'promot_time' => $promot_time,
                                        'type' => $request->get('type'),
                                        'image' => $store_name,
                                        'file_type' => 'video', // because it is video (DO not change it.)
                                        'thumbnail' => $store_name_thumbnail,
                                    ]);
                                    if ($post) {
                                        $followers = DB::table('followers')
                                            ->where('followed_to', $request->get('user_id'))
                                            ->where('is_followed', '1')
                                            ->join('user_detail', 'user_detail.user_id', '=', 'followers.followed_by')
                                            ->select('user_detail.fcm_token')
                                            ->get();

                                        if (count($followers) > 0) {
                                            $noti = new Notification();
                                            $tokens = array();
                                            foreach ($followers as $key => $value) {
                                                array_push($tokens, $value->fcm_token);
                                            }

                                            $noti_array = [
                                                'title' => "New Post",
                                                'body' => "New Post is added.",
                                                'tokens' => $tokens,
                                            ];

                                            $status = $noti->sendNotification($noti_array);
                                            if ($status == 1) {
                                                return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully.'], 200);
                                            } else {
                                                return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                            }
                                        } else {
                                            return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                        }
                                    } else {
                                        return response()->json(['status_code' => 1, 'message' => 'Oops! Something went wrong.'], 200);
                                    }
                                } else {
                                    return response()->json(['status_code' => 1, 'message' => 'Error in File Format.'], 200);
                                }
                            }
                        }
                    } else {

                        $store_name = "";
                        $store_name_thumbnail = "/uploads/posts/thumbnail/default-video.png";
                        // -KR//

                        if ($request->file('video')) {

                            $file = $request->file('video');
                            $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . "VIDEO" . "_" . rand() . "." . $file->getClientOriginalExtension());
                            $file->move(public_path() . '/uploads/posts/', $imageName);
                            $store_name = '/uploads/posts/' . $imageName;

                            //KR-//
                            // $cnt = 1;
                            // foreach ($request->file('image') as $file) {
                            //     $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." .$file->getClientOriginalExtension());
                            //     $file->move(public_path() . '/uploads/posts/', $imageName);
                            //     if($cnt != 1)
                            //     {
                            //         $store_name = $store_name . "," . '/uploads/posts/' . $imageName;
                            //     }
                            //     else
                            //     {
                            //         $store_name = $store_name . '/uploads/posts/' . $imageName;
                            //     }
                            //     $cnt++;
                            // }
                            //-KR//

                            if ($request->get('type') === "state") {
                                $promot_time = 12;
                            } else if ($request->get('type') === "world") {
                                $promot_time = 24;
                            } else {
                                $promot_time = null;
                            }

                            $post = Posts::create([
                                'user_id' => $request->get('user_id'),
                                'title' => $request->get('title'),
                                'status' => '1',
                                'promot_time' => $promot_time,
                                'type' => $request->get('type'),
                                'image' => $store_name,
                                'file_type' => 'video', // because it is video (DO not change it.)
                                'thumbnail' => $store_name_thumbnail,
                            ]);
                            if ($post) {
                                $followers = DB::table('followers')
                                    ->where('followed_to', $request->get('user_id'))
                                    ->where('is_followed', '1')
                                    ->join('user_detail', 'user_detail.user_id', '=', 'followers.followed_by')
                                    ->select('user_detail.fcm_token')
                                    ->get();

                                if (count($followers) > 0) {
                                    $noti = new Notification();
                                    $tokens = array();
                                    foreach ($followers as $key => $value) {
                                        array_push($tokens, $value->fcm_token);
                                    }

                                    $noti_array = [
                                        'title' => "New Post",
                                        'body' => "New Post is added.",
                                        'tokens' => $tokens,
                                    ];

                                    $status = $noti->sendNotification($noti_array);
                                    if ($status == 1) {
                                        return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully.'], 200);
                                    } else {
                                        return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                    }
                                } else {
                                    return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                                }
                            } else {
                                return response()->json(['status_code' => 1, 'message' => 'Oops! Something went wrong.'], 200);
                            }
                        } else {
                            return response()->json(['status_code' => 1, 'message' => 'Error in File Format.'], 200);
                        }
                    }
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method is not allowed'], 405);
        }
    }

    // Not Used currently 0903 After
    // Use this function for add post thumbnail on user's posts.

    public function addPostThumbnail(Request $request)
    {
        if ($request->method() == 'POST') {
            try {
                // $messages = array(
                //     "upload_count.max" => 'The :attribute field cannot be more than 3.',
                // );
                $validator = Validator::make($request->all(), [
                    'user_id' => 'required',
                    'title' => 'required',
                    'type' => 'required',
                    'image' => 'required',
                    //'upload_count' => 'max:3',
                    // array('type' => array('upload_count:type,3')),
                    // $messages
                ]);
                if ($validator->fails()) {
                    return response()->json(['status_code' => 1, 'message' => $validator->errors()->first()], 406);
                }
                // if (count($request->image) > 5) {
                //     return response()->json(['status_code' => 1, 'message' => 'For a single post, Only 5 or less image/video are allowed.'], 404);
                // }
                // KR-//
                $store_name = "";
                // -KR//

                // dd($request);
                if ($request->file('image')) {

                    // dd(count($request->file('type')));
                    // $store_name = "";

                    $file = $request->file('image');
                    // $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . "VIDEO" . "_" . rand() . "." . $file->getClientOriginalExtension());
                    // $file->move(public_path() . '/uploads/posts/', $imageName);
                    // $store_name = '/uploads/posts/' . $imageName;

                    $store_name_thumbnail = null;
                    if ($request->file('thumbnail')) {
                        $file_thumbnail = $request->file('thumbnail');
                        $imageName_thumbnail = str_replace(":", "_", date('Y-m-d_His') . "_" . "THUMB" . "." . rand() . "." . $file_thumbnail->getClientOriginalExtension());
                        $file_thumbnail->move(public_path() . '/uploads/posts/thumbnail/', $imageName_thumbnail);
                        $store_name_thumbnail = '/uploads/posts/thumbnail/' . $imageName_thumbnail;
                    }

                    //KR-//
                    // $cnt = 1;
                    // foreach ($request->file('image') as $file) {
                    //     $imageName = str_replace(":", "_", date('Y-m-d_His') . "_" . rand() . "." .$file->getClientOriginalExtension());
                    //     $file->move(public_path() . '/uploads/posts/', $imageName);
                    //     if($cnt != 1)
                    //     {
                    //         $store_name = $store_name . "," . '/uploads/posts/' . $imageName;
                    //     }
                    //     else
                    //     {
                    //         $store_name = $store_name . '/uploads/posts/' . $imageName;
                    //     }
                    //     $cnt++;
                    // }
                    //-KR//

                    if ($request->get('type') === "state") {
                        $promot_time = 12;
                    } else if ($request->get('type') === "world") {
                        $promot_time = 24;
                    } else {
                        $promot_time = null;
                    }

                    $post = Posts::create([
                        'user_id' => $request->get('user_id'),
                        'title' => $request->get('title'),
                        'status' => '1',
                        'promot_time' => $promot_time,
                        'type' => $request->get('type'),
                        'image' => $store_name,
                        // 'file_type' => 'image', // because it is image (DO not change it.)
                        'thumbnail' => $store_name_thumbnail,
                    ]);
                    if ($post) {
                        $followers = DB::table('followers')
                            ->where('followed_to', $request->get('user_id'))
                            ->where('is_followed', '1')
                            ->join('user_detail', 'user_detail.user_id', '=', 'followers.followed_by')
                            ->select('user_detail.fcm_token')
                            ->get();

                        if (count($followers) > 0) {
                            $noti = new Notification();
                            $tokens = array();
                            foreach ($followers as $key => $value) {
                                array_push($tokens, $value->fcm_token);
                            }

                            $noti_array = [
                                'title' => "New Post",
                                'body' => "New Post is added.",
                                'tokens' => $tokens,
                            ];

                            $status = $noti->sendNotification($noti_array);
                            if ($status == 1) {
                                return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully.'], 200);
                            } else {
                                return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                            }
                        } else {
                            return response()->json(['status_code' => 0, 'message' => 'Post Added Succesfully..'], 200);
                        }
                    } else {
                        return response()->json(['status_code' => 1, 'message' => 'Try again.'], 404);
                    }
                } else {
                    return response()->json(['status_code' => 1, 'message' => 'Error in Image Format.'], 404);
                }
            } catch (Exception $ex) {
                return response()->json(['status_code' => 1, 'message' => $ex->getMessage()], 404);
            }
        } else {
            return response()->json(['status_code' => 1, 'message' => 'Method is not allowed'], 405);
        }
    }
}


