<?php

namespace App\Http\Controllers;

use App\Http\Requests\DealMessageRequest;
use App\Models\DealMessage;
use App\Models\Purchase;
use Illuminate\Support\Facades\Storage;

class DealMessageController extends Controller
{
    /**
     * メッセージ送信
     */
    public function store(DealMessageRequest $request, Purchase $purchase)
    {
        $user = auth()->user();

        // 購入者 or 出品者 以外は送れない
        abort_unless(
            $purchase->isBuyer($user->id) || $purchase->isSeller($user->id),
            403
        );

        // 出品者IDを確実に取るため、item が未ロードならロード
        $purchase->loadMissing('item');

        $buyerId  = (int) $purchase->user_id;        // 購入者
        $sellerId = (int) $purchase->item->user_id;  // 出品者

        // 自分がbuyerなら相手はseller、そうでなければbuyer
        $receiverId = ((int)$user->id === $buyerId) ? $sellerId : $buyerId;

        $data = [
            'purchase_id' => $purchase->id,
            'sender_id'   => $user->id,
            'receiver_id' => $receiverId,
            'body'        => $request->body,
        ];

        // 画像がある場合
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('deal_messages', 'public');
            $data['image_path'] = $path;
        }

        DealMessage::create($data);

        // FN004：最新メッセージ時刻更新
        $purchase->update([
            'last_message_at' => now(),
        ]);

        return redirect()
            ->route('deals.show', $purchase)
            ->with('success', 'メッセージを送信しました');
    }

    /**
     * メッセージ編集（本文のみ）
     * ※同じ DealMessageRequest を使う：本文必須・画像任意
     * ※編集は「本文だけ」を反映（画像は変更しない）
     */
    public function update(DealMessageRequest $request, Purchase $purchase, DealMessage $message)
    {
        $user = auth()->user();

        // 購入者 or 出品者 以外は操作できない（画面に来れてもガード）
        abort_unless(
            $purchase->isBuyer($user->id) || $purchase->isSeller($user->id),
            403
        );

        // この取引のメッセージか確認（他取引の改ざん防止）
        abort_unless((int)$message->purchase_id === (int)$purchase->id, 404);

        // 送信者本人のみ編集可能
        abort_unless((int)$message->sender_id === (int)$user->id, 403);

        // 本文のみ更新（画像はそのまま）
        $message->update([
            'body' => $request->body,
        ]);

        // 更新したら last_message_at を更新したい場合（任意）
        // $purchase->update(['last_message_at' => now()]);

        return redirect()
            ->route('deals.show', $purchase)
            ->with('success', 'メッセージを更新しました');
    }

    /**
     * メッセージ削除
     */
    public function destroy(Purchase $purchase, DealMessage $message)
    {
        $user = auth()->user();

        // 購入者 or 出品者 以外は操作できない
        abort_unless(
            $purchase->isBuyer($user->id) || $purchase->isSeller($user->id),
            403
        );

        // この取引のメッセージか確認
        abort_unless((int)$message->purchase_id === (int)$purchase->id, 404);

        // 送信者本人のみ削除可能
        abort_unless((int)$message->sender_id === (int)$user->id, 403);

        // 画像も物理削除したい場合はここ（任意）
        if (!empty($message->image_path)) {
            Storage::disk('public')->delete($message->image_path);
        }

        $message->delete();

        return redirect()
            ->route('deals.show', $purchase)
            ->with('success', 'メッセージを削除しました');
    }
}