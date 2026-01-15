<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\News;
use App\Models\VideoPost;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(50, $perPage));

        $q = Comment::query()->with('user');

        if ($request->filled('parent_id')) {
            $q->where('parent_id', $request->integer('parent_id'));
        } elseif ($request->filled('news_id')) {
            $q->where('commentable_type', News::class)
                ->where('commentable_id', $request->integer('news_id'))
                ->whereNull('parent_id');
        } elseif ($request->filled('video_post_id')) {
            $q->where('commentable_type', VideoPost::class)
                ->where('commentable_id', $request->integer('video_post_id'))
                ->whereNull('parent_id');
        } else {
            return response()->json([
                'message' => 'Укажи parent_id или news_id или video_post_id',
            ], 422);
        }

        $comments = $q->orderBy('created_at')->orderBy('id')
            ->cursorPaginate($perPage);

        return response()->json([
            'data' => $comments->items(),
            'next_cursor' => optional($comments->nextCursor())->encode(),
            'prev_cursor' => optional($comments->previousCursor())->encode(),
            'per_page' => $perPage,
        ]);
    }

    public function store(StoreCommentRequest $request)
    {
        $userId = $request->integer('user_id');

        // определяем цель
        if ($request->filled('parent_comment_id')) {
            $parent = Comment::query()->findOrFail($request->integer('parent_comment_id'));

            // Ответ наследует commentable от родителя
            $commentableType = $parent->commentable_type;
            $commentableId   = $parent->commentable_id;

            $parentId = $parent->id;
        } elseif ($request->filled('news_id')) {
            $commentableType = News::class;
            $commentableId   = $request->integer('news_id');
            $parentId = null;
        } else { // video_post_id
            $commentableType = VideoPost::class;
            $commentableId   = $request->integer('video_post_id');
            $parentId = null;
        }

        $comment = Comment::create([
            'user_id' => $userId,
            'body' => $request->string('body'),
            'commentable_type' => $commentableType,
            'commentable_id' => $commentableId,
            'parent_id' => $parentId,
        ]);

        return response()->json([
            'data' => $comment->load('user'),
        ], 201);
    }

    public function show(Comment $comment)
    {
        return response()->json([
            'data' => $comment->load(['user','parent']),
        ]);
    }

    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        // “от лица пользователя”: примитивная проверка
        if ($comment->user_id !== $request->integer('user_id')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comment->update([
            'body' => $request->string('body'),
        ]);

        return response()->json([
            'data' => $comment->fresh()->load('user'),
        ]);
    }

    public function destroy(Request $request, Comment $comment)
    {
        $userId = (int) $request->input('user_id');
        if ($comment->user_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comment->delete();

        return response()->json(null, 204);
    }
}

