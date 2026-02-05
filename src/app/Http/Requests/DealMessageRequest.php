<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DealMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body'  => ['required', 'string', 'max:400'],

            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => '本文を入力してください',
            'body.max'      => '本文は400文字以内で入力してください',
            'image.image'   => '画像ファイルを選択してください',
            'image.mimes'   => '「.png」「.jpg」「.jpeg」形式でアップロードしてください',
            'image.max'     => '画像サイズは2MB以内にしてください',
        ];
    }
}