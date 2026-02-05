<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;

class DealChatController extends Controller
{
    public function show(Purchase $purchase, Request $request)
    {
        $user = $request->user();

        // 関係ない人はアクセス不可
        abort_unless(
            $purchase->isBuyer($user->id) || $purchase->isSeller($user->id),
            403
        );

        // 必要なリレーション（N+1防止）
        $purchase->load([
            'item.user',
            'messages.sender',
        ]);

        // 既読更新（FN005）
        $purchase->markAsReadBy($user->id);

        // 左側の「取引中一覧」
        $otherPurchases = Purchase::query()
            ->with(['item.user']) // ← item.user まで取っておくとビューで安全
            ->where('status', Purchase::STATUS_TRADING)
            ->whereKeyNot($purchase->id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id) // 購入者
                  ->orWhereHas('item', function ($q2) use ($user) {
                      $q2->where('user_id', $user->id); // 出品者
                  });
            })
            ->orderByDesc('last_message_at')
            ->limit(30) // ← 件数増加対策（必要なら調整）
            ->get();

        // 未読件数（動的プロパティが気になる場合は accessor 推奨）
        $otherPurchases->each(function (Purchase $p) use ($user) {
            $p->unread_count = $p->unreadCountFor($user->id);
        });

        return view('deals.show', [
            'purchase'       => $purchase,
            'messages'       => $purchase->messages,
            'otherPurchases' => $otherPurchases,
        ]);
    }
}