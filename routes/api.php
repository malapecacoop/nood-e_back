<?php

use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {

    // not authenticated routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('forgot-password');
        Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('reset-password');
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/invitation/{user}', [AuthController::class, 'acceptInvitation'])->name('invitation');
    });
    // authenticated routes
    Route::middleware('auth:api')->group(function() {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/set-password', [AuthController::class, 'setPassword']);

        Route::get('/events', [EventController::class, 'index']);
        Route::get('/events/rooms/{room?}', [EventController::class, 'indexRooms']);
        Route::get('/events/{event}', [EventController::class, 'show']);
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{event}', [EventController::class, 'update']);
        Route::put('/events/{event}/recurrency-end', [EventController::class, 'updateRecurrencyEnd']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);
        
        Route::post('/discussions/{discussion}/{topic}', [CommentController::class, 'store']);
        Route::put('/discussions/{discussion}/{topic}/{comment}', [CommentController::class, 'update']);
        Route::delete('/discussions/{discussion}/{topic}/{comment}', [CommentController::class, 'destroy']);
        
        Route::get('/discussions', [DiscussionController::class, 'index']);
        Route::get('/discussions/{discussion}', [DiscussionController::class, 'show']);
        Route::post('/discussions', [DiscussionController::class, 'store']);
        Route::put('/discussions/{discussion}', [DiscussionController::class, 'update']);
        Route::delete('/discussions/{discussion}', [DiscussionController::class, 'destroy']);
        
        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
        Route::delete('/organizations/{organization}/{user}', [OrganizationController::class, 'userDestroy']);
        Route::post('/organizations', [OrganizationController::class, 'store']);
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);
        
        Route::get('/rooms', [RoomController::class, 'index']);
        Route::get('/rooms/free', [RoomController::class, 'showFree']);
        Route::get('/rooms/{room}', [RoomController::class, 'show']);
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
        
        Route::get('/discussions/{discussion}/{topic}', [TopicController::class, 'show']);
        Route::post('/discussions/{discussion}', [TopicController::class, 'store']);
        Route::put('/discussions/{discussion}/{topic}', [TopicController::class, 'update']);
        Route::delete('/discussions/{discussion}/{topic}', [TopicController::class, 'destroy']);
        
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::post('/users/mass-invite', [UserController::class, 'massInvite']);
        Route::put('/users/{user}/basic', [UserController::class, 'updateBasicData']);
        Route::put('/users/{trashed_user}/restore', [UserController::class, 'restore']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        Route::get('/roles', [RoleController::class, 'index']);
    });
});