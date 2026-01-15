<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required','string','min:1'],
            'user_id' => ['required','integer','exists:users,id'],

            // один из вариантов цели
            'news_id' => ['nullable','integer','exists:news,id'],
            'video_post_id' => ['nullable','integer','exists:video_posts,id'],
            'parent_comment_id' => ['nullable','integer','exists:comments,id'],

            // курсор/лимит не здесь
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $targets = array_filter([
                $this->input('news_id'),
                $this->input('video_post_id'),
                $this->input('parent_comment_id'),
            ], fn($x) => !is_null($x));

            if (count($targets) !== 1) {
                $v->errors()->add('target', 'Укажи ровно одну цель: news_id или video_post_id или parent_comment_id.');
            }
        });
    }
}

