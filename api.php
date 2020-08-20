<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Show Api help Page...
Route::get('help', 'APIHelpController@index');

// Route::middleware('jwt.auth')->get('users', function (Request $request) {
//     //return auth()->user();
// });

// Route::middleware('jwt.auth')->group('user/',function(Request $request){

// Route::get('getUser/{id}', 'APIUserController@getUser');

// });

Route::prefix('user/')->group(function () {

    // Social  App
    Route::post('register', 'APIRegisterController@register');
    Route::post('login', 'APILoginController@login');
    Route::get('logout', 'APILoginController@logout');
    Route::post('changePassword', 'APILoginController@changePassword');
    Route::post('forgotPassword', 'APIUserController@forgotPassword');

    Route::post('saveFcmToken', 'APILoginController@saveFcmToken');

    //Country list
    Route::get('getCountryList', 'APIRegisterController@getCountryList');

    //State list
    Route::get('getStateList/{id}', 'APIRegisterController@getStateList');

    //City list
    Route::get('getCityList/{id}', 'APIRegisterController@getCityList');

    //Followers

    Route::post('addFollower', 'APIFollowersController@addFollower');
    Route::post('removeFollower', 'APIFollowersController@removeFollower');

    // Route::post('sendRequestFollower', 'APIFollowersController@sendRequestFollower');
    // Route::post('acceptRequestFollower', 'APIFollowersController@acceptRequestFollower');
    // Route::post('rejectRequestFollower', 'APIFollowersController@rejectRequestFollower');

    Route::get('getFollowerList/{id}', 'APIFollowersController@getFollowerList');
    Route::get('getFollowerNotificationList/{id}', 'APINotificationController@getFollowerNotificationList');
    Route::post('viewFollowerNotification', 'APINotificationController@viewFollowerNotification');

    //Product
    Route::post('addProduct', 'APIProductController@addProduct');
    Route::get('getProductList/{id}', 'APIProductController@getProductList');

    //Competititon
    Route::post('addCompetition', 'APICompetitionController@addCompetition');
    Route::post('makeUserEliminated', 'APICompetitionController@makeUserEliminated');
    Route::get('getEliminateUserList', 'APICompetitionController@getEliminateUserList');
    Route::get('getUserCompetitionList', 'APICompetitionController@getUserCompetitionList');

    Route::post('signUpForCompetition', 'APICompetitionController@signUpForCompetition');
    Route::post('checkForSignUpCompetition', 'APICompetitionController@checkForSignUpCompetition');
    Route::post('addCompetitionPost', 'APICompetitionController@addCompetitionPost');
    Route::get('getCompetitionPostList/{id}', 'APICompetitionController@getCompetitionPostList');
    Route::post('addCompetitionPostLike', 'APICompetitionController@addCompetitionPostLike');
    Route::get('getCompetitionPost/{id}', 'APICompetitionController@getCompetitionPost');


    //Chat
    Route::post('addChat', 'APIChatController@addChat');
    Route::get('deleteChat/{id}', 'APIChatController@deleteChat');
    Route::get('getChatList', 'APIChatController@getChatList');
    Route::get('getWorldChatList/{id}', 'APIChatController@getWorldChatList');
    Route::get('getStateChatList/{id}', 'APIChatController@getStateChatList');
    Route::get('getFollowingChatList/{id}', 'APIChatController@getFollowingChatList');

    //Posts
    Route::post('addPost', 'APIPostsController@addPost');
    Route::post('addPostWithVideo', 'APIPostsController@addPostWithVideo');
    Route::post('addPostThumbnail', 'APIPostsController@addPostThumbnail');

    Route::get('postDetailsList/{id}', 'APIPostsController@postDetailsList');
    Route::get('deletePost/{id}', 'APIPostsController@deletePost');
    Route::post('editPostDetails', 'APIPostsController@editPostDetails');
    Route::post('deletePostImage', 'APIPostsController@deletePostImage');

    Route::post('searchPost', 'APIPostsController@searchPost');

    Route::get('getPostDetails/{post_id}/{user_id}', 'APIPostsController@getPostDetails');

    //postcomments
    Route::post('addPostComments', 'APIPostCommentsController@addPostComments');
    Route::post('editPostComments', 'APIPostCommentsController@editPostComments');
    Route::get('deleteComment/{id}', 'APIPostCommentsController@deleteComment');
    Route::get('commentList/{id}', 'APIPostCommentsController@commentList');

    //Post Likes
    Route::post('like', 'APIPostLikesController@like');
    Route::post('addLike', 'APIPostLikesController@addLike');
    Route::get('deleteLike/{id}', 'APIPostLikesController@deleteLike');
    Route::get('likeList/{id}', 'APIPostLikesController@likeList');

    //Post Rate
    Route::post('addRates', 'APIPostRatesController@addRates');
    Route::get('deleteRate/{id}', 'APIPostRatesController@deleteRate');
    Route::get('rateList/{id}', 'APIPostRatesController@rateList');

    //Post Report
    Route::post('addReports', 'APIPostReportsController@addReports');

    //User Account
    Route::get('getUserAccountDetail/{id}', 'APIAccountController@getUserAccountDetail');
    Route::get('getUser/{id}', 'APIUserController@getUser');
    Route::get('getUserList', 'APIUserController@getUserList');
    Route::post('editUserProfileDetails', 'APIUserController@editUserProfileDetails');
    Route::post('editUserProfile', 'APIUserController@editUserProfile');
    Route::get('getUserProfileDetails/{id}', 'APIUserController@getUserProfileDetails');
    Route::post('setPrivacy', 'APIUserController@setPrivacy');
    Route::post('setNotificationSettings', 'APIUserController@setNotificationSettings');
    Route::post('addProfilePost', 'APIAccountController@addProfilePost');

    //Block User
    Route::get('getBlockUserList/{id}', 'APIUserController@getBlockUserList');
    Route::post('addBlockUser', 'APIUserController@addBlockUser');

    //Conversation
    Route::post('addConversation', 'APIConversationController@addConversation');
    Route::get('getConversationListBySender/{id}', 'APIConversationController@getConversationListBySender');
    Route::get('getConversationListByReceiver/{id}', 'APIConversationController@getConversationListByReceiver');
    Route::get('getConversationList/{id}', 'APIConversationController@getConversationList');

    //Conversation Chat
    Route::post('addConversationChat', 'APIConversationController@addConversationChat');
    // Route::get('getConversationChatList/{conversation_id}/{user_id}', 'APIConversationController@getConversationChatList');
    Route::get('getConversationChatList/{conversation_id}', 'APIConversationController@getConversationChatList');

    // Conversation Type
    Route::get('getConversationTypeList', 'APIConversationTypeController@getConversationTypeList');
    Route::post('addConversationType', 'APIConversationTypeController@addConversationType');

    // Notification
    Route::get('send', 'APINotificationController@sendNotification');
    Route::post('sendTest', 'APINotificationController@sendTestNotification');
    Route::get('getNotificationList/{id}', 'APINotificationController@getNotificationList');

    // Round Winner List
    Route::get('getCompetitionWinnerList', 'APICompetitionController@getCompetitionWinnerList');

    // Competition Post Details
    Route::get('getCompetitionPostDetails/{comp_post_id}/{user_id}', 'APICompetitionController@getCompetitionPostDetails');

    // Add Competition Post Report
    Route::post('addCompetitionPostReports', 'APICompetitionController@addCompetitionPostReports');

    // Competition Competition Rank Wise User List
    Route::get('getCompetitionRankWiseUserList/{id}', 'APICompetitionController@getCompetitionRankWiseUserList');

});
