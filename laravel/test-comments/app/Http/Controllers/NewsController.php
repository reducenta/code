<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(50, $perPage));

        $news = News::query()
            ->orderBy('created_at', 'desc')
            ->cursorPaginate($perPage);

        return response()->json([
            'data' => $news->items(),
            'next_cursor' => optional($news->nextCursor())->encode(),
            'prev_cursor' => optional($news->previousCursor())->encode(),
            'per_page' => $perPage,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],
        ]);

        $news = News::create($data);

        return response()->json(['data' => $news], 201);
    }

    public function show(News $news, Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(50, $perPage));

        $comments = $news->rootComments()
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate($perPage);

        return response()->json([
            'data' => $news,
            'comments' => [
                'data' => $comments->items(),
                'next_cursor' => optional($comments->nextCursor())->encode(),
                'prev_cursor' => optional($comments->previousCursor())->encode(),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function update(News $news, Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],
        ]);

        $news->update($data);

        return response()->json(['data' => $news->fresh()]);
    }

    public function destroy(News $news)
    {
        $news->delete();

        return response()->json(null, 204);
    }
}


