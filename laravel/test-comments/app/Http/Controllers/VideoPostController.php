<?php

namespace App\Http\Controllers;

use App\Models\VideoPost;
use Illuminate\Http\Request;

class VideoPostController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(50, $perPage));

        $posts = VideoPost::query()
            ->orderBy('created_at', 'desc')
            ->cursorPaginate($perPage);

        return response()->json([
            'data' => $posts->items(),
            'next_cursor' => optional($posts->nextCursor())->encode(),
            'prev_cursor' => optional($posts->previousCursor())->encode(),
            'per_page' => $perPage,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],
        ]);

        $post = VideoPost::create($data);

        return response()->json([
            'data' => $post,
        ], 201);
    }

    public function show(VideoPost $videoPost, Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(50, $perPage));

        $comments = $videoPost->rootComments()
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate($perPage);

        return response()->json([
            'data' => $videoPost,
            'comments' => [
                'data' => $comments->items(),
                'next_cursor' => optional($comments->nextCursor())->encode(),
                'prev_cursor' => optional($comments->previousCursor())->encode(),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function update(VideoPost $videoPost, Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],
        ]);

        $videoPost->update($data);

        return response()->json([
            'data' => $videoPost->fresh(),
        ]);
    }

    public function destroy(VideoPost $videoPost)
    {
        $videoPost->delete();

        return response()->json(null, 204);
    }
}
