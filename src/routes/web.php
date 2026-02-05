<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\VerifyCsrfToken;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use App\Http\Middleware\FortifyLoginFormRequest;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\DealChatController;
use App\Http\Controllers\DealMessageController;
use App\Http\Controllers\DealController;

/*
|--------------------------------------------------------------------------
| 公開ページ（ゲストOK）
|--------------------------------------------------------------------------
*/

Route::get('/', [ItemController::class, 'index'])->name('items.index');
Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');

// 認証待ち誘導
Route::get('/verify/prompt', fn() => view('auth.verify-prompt'))
    ->middleware('auth')->name('verify.prompt');
Route::get('/verify/open-mail', fn() => redirect()->away(config('services.mailhog.url', 'http://localhost:8025')))
    ->middleware('auth')->name('verify.mailhog');
Route::get('/email/verify', fn() => view('auth.verify-prompt'))
    ->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill(); // 認証を確定（Verified イベント発火）
    return redirect()->route('items.index', ['tab' => 'mylist']); // ← ここをお好みで
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Fortify の認証 POST（FormRequest はパイプラインで実行）
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['web', 'guest', 'throttle:login', FortifyLoginFormRequest::class])
    ->name('login');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware(['web'])
    ->name('logout');

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware(['web', 'guest'])
    ->name('register');

/*
|--------------------------------------------------------------------------
| 自前の認証 GET 表示（Fortify の views は無効にしている）
|--------------------------------------------------------------------------
*/
Route::get('/register', [AuthController::class, 'showRegisterForm'])
    ->name('auth.register.form')->middleware('guest');

Route::get('/login', [AuthController::class, 'showLoginForm'])
    ->name('auth.login.form')->middleware('guest');

/*
|--------------------------------------------------------------------------
| ログイン必須
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/mypage', [ProfileController::class, 'show'])->name('mypage');

    Route::get('/mypage/profile', [ProfileController::class, 'create'])->name('profile.create');
    Route::post('/mypage/profile', [ProfileController::class, 'store'])->name('profile.store');

    Route::get('/sell', [ItemController::class, 'create'])->name('items.create');
    Route::post('/sell', [ItemController::class, 'store'])->name('items.store');

    Route::post('/items/{item}/like', [LikeController::class, 'toggle'])->name('like.toggle');
    Route::post('/items/{item}/comments', [CommentController::class, 'store'])
        ->middleware('auth')
        ->name('comments.store');

    Route::prefix('purchase')->name('purchase.')->group(function () {
        Route::get('/{item}',  [PurchaseController::class, 'show'])->name('show');
        Route::post('/{item}', [PurchaseController::class, 'store'])->name('store');
        Route::get('/address/{item}',  [PurchaseController::class, 'editAddress'])->name('address.edit');
        Route::post('/address/{item}', [PurchaseController::class, 'updateAddress'])->name('address.update');
        Route::get('/{item}/success', [PurchaseController::class, 'success'])->name('success');
        Route::get('/{item}/cancel',  [PurchaseController::class, 'cancel'])->name('cancel');
    });

     // 取引チャット画面
    Route::get('/deals/{purchase}', [DealChatController::class, 'show'])
        ->name('deals.show');

    // 取引メッセージ投稿
    Route::post('/deals/{purchase}/messages', [DealMessageController::class, 'store'])
        ->name('deals.messages.store');
        
    // 追加：編集・削除
    Route::patch('/deals/{purchase}/messages/{message}', [DealMessageController::class, 'update'])
        ->name('deals.messages.update');

    Route::delete('/deals/{purchase}/messages/{message}', [DealMessageController::class, 'destroy'])
        ->name('deals.messages.destroy');

    Route::post('/deals/{purchase}/complete', [DealController::class, 'complete'])
    ->name('deals.complete');

Route::post('/deals/{purchase}/complete-seller', [DealController::class, 'completeSeller'])
    ->name('deals.complete_seller');
});

// Webhook は公開（認証なし）
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('stripe.webhook');
