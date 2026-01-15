<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required','string','min:1'],
            'user_id' => ['required','integer','exists:users,id'], // “от лица пользователя”
        ];
    }
}

