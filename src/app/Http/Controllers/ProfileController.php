<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\Purchase;
use App\Models\Review;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * プロフィール編集画面表示
     */
    public function create()
    {
        $user = auth()->user()->loadMissing('address');
        $address = $user->address;

        return view('mypage.profile_setup', compact('user', 'address'));
    }

    /**
     * プロフィール更新
     */
    public function store(ProfileRequest $request)
    {
        $user = Auth::user();

        DB::transaction(function () use ($request, $user) {
            // プロフィール画像
            if ($request->hasFile('profile_image')) {
                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }

                $user->profile_image = $request->file('profile_image')->store('profiles', 'public');
            }

            // ユーザー情報更新
            $user->name = $request->input('name');
            $user->save();

            // 住所情報（フォームのキーが英語/日本語どちらでも受ける互換対応）
            $payload = [
                'postcode' => $request->input('postcode', $request->input('郵便番号')),
                'address'  => $request->input('address', $request->input('住所')),
                'building' => $request->input('building', $request->input('建物名')),
            ];

            UserAddress::updateOrCreate(
                ['user_id' => $user->id],
                $payload
            );
        });

        return redirect()
            ->route('items.index')
            ->with('success', 'プロフィールを更新しました。');
    }

    /**
     * マイページ（出品 / 購入 / 取引中）
     */
    public function show(Request $request)
    {
        $user = auth()->user();
        $tab  = $request->query('tab', 'sell');

        // 受けた評価の平均（四捨五入で整数表示）
        $avg = Review::where('ratee_id', $user->id)->avg('score');
        $rating = is_null($avg) ? null : (int) round($avg);

        // 取引中（未読数の集計にも使用）
        $tradingPurchases = Purchase::with(['item.user', 'messages'])
            ->whereIn('status', [Purchase::STATUS_TRADING, Purchase::STATUS_BUYER_REVIEWED])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('item', function ($q2) use ($user) {
                      $q2->where('user_id', $user->id);
                  });
            })
            ->get();

        $tradingPurchases->each(function ($purchase) use ($user) {
            $purchase->unread_count = $purchase->unreadCountFor($user->id);
        });

        $unreadDealCount = $tradingPurchases->where('unread_count', '>', 0)->count();

        // 共通で渡す値
        $base = [
            'user'            => $user,
            'tab'             => $tab,
            'unreadDealCount' => $unreadDealCount,
            'rating'          => $rating,
        ];

        if ($tab === 'deal') {
            $purchases = $tradingPurchases->sortByDesc('last_message_at')->values();

            return view('mypage.index', $base + [
                'tab'       => 'deal',
                'purchases' => $purchases,
            ]);
        }

        if ($tab === 'buy') {
            $purchases    = $user->purchases()->with('item')->latest()->get();
            $items        = $purchases->pluck('item')->filter();
            $purchasedIds = $items->pluck('id')->all();

            return view('mypage.index', $base + [
                'items'        => $items,
                'purchasedIds' => $purchasedIds,
            ]);
        }

        $items = $user->items()->latest()->get();

        return view('mypage.index', $base + [
            'tab'          => 'sell',
            'items'        => $items,
            'purchasedIds' => [],
        ]);
    }
}