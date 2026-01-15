<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\NewsController;
use App\Http\Controllers\VideoPostController;
use App\Http\Controllers\CommentController;

Route::apiResource('news', NewsController::class)->only(['index','store','show','update','destroy']);
Route::apiResource('video-posts', VideoPostController::class)->only(['index','store','show','update','destroy']);

// Комментарии: CRUD + листинг
Route::apiResource('comments', CommentController::class)->only(['index','store','show','update','destroy']);

