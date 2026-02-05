<?php

namespace App\Actions\Fortify;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        /** @var \App\Http\Requests\RegisterRequest $req */
        $req = app(RegisterRequest::class);

        // ★ 検証前に整形（prepareForValidation 相当）
        $normalized = $input;

        if (isset($normalized['name'])) {
            $normalized['name'] = trim($normalized['name']);
        }

        if (isset($normalized['email'])) {
            $normalized['email'] = mb_strtolower(trim($normalized['email']));
        }

        $validator = Validator::make(
            $normalized,
            $req->rules(),
            method_exists($req, 'messages') ? $req->messages() : [],
            method_exists($req, 'attributes') ? $req->attributes() : []
        );

        // ★ RegisterRequest に withValidator(...) を書いているなら反映
        if (method_exists($req, 'withValidator')) {
            $req->withValidator($validator);
        }

        $data = $validator->validate();

        // ✅ ユーザー作成
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // ✅ 登録直後にメール認証を送る（初回メールが届かない問題の解決）
        event(new Registered($user));
        \Log::debug('CreateNewUser called', ['email' => $user->email]);
        return $user;
    }
}