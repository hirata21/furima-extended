<?php

namespace App\Http\Controllers;

use App\Mail\DealCompletedMail;
use App\Models\Purchase;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DealController extends Controller
{
    public function complete(Request $request, Purchase $purchase)
    {
        $userId = (int) auth()->id();

        abort_unless($purchase->isBuyer($userId), 403);

        if ($purchase->hasBuyerRated()) {
            return redirect()
                ->route('items.index')
                ->with('success', 'すでに評価済みです');
        }

        $score = $this->validateScore($request);

        $sellerId = $purchase->sellerId();
        abort_unless($sellerId, 404);

        Review::firstOrCreate(
            [
                'purchase_id' => $purchase->id,
                'rater_id'    => $userId,
            ],
            [
                'ratee_id' => (int) $sellerId,
                'score'    => $score,
            ]
        );

        $purchase->update([
            'status'       => Purchase::STATUS_BUYER_REVIEWED,
            'completed_at' => null,
        ]);

        // 出品者へ通知メール（必要なリレーションだけ補完）
        $purchase->loadMissing('item.user');
        $seller = $purchase->item?->user;

        if ($seller && !empty($seller->email)) {
            Mail::to($seller->email)->send(new DealCompletedMail($purchase));
        }

        return redirect()
            ->route('items.index')
            ->with('success', '評価を送信しました。出品者の評価待ちです。');
    }

    public function completeSeller(Request $request, Purchase $purchase)
    {
        $userId = (int) auth()->id();

        abort_unless($purchase->isSeller($userId), 403);
        abort_unless($purchase->hasBuyerRated(), 403);

        if ($purchase->hasSellerRated()) {
            return redirect()
                ->route('items.index')
                ->with('success', 'すでに評価済みです');
        }

        $score = $this->validateScore($request);
        $buyerId = (int) $purchase->user_id;

        Review::firstOrCreate(
            [
                'purchase_id' => $purchase->id,
                'rater_id'    => $userId,
            ],
            [
                'ratee_id' => $buyerId,
                'score'    => $score,
            ]
        );

        $purchase->syncCompletionStatus();

        return redirect()
            ->route('items.index')
            ->with('success', '評価を送信しました。取引が完了しました。');
    }

    /**
     * ★評価（0.5〜5.0 / 0.5刻み）を検証して float を返す
     */
    private function validateScore(Request $request): float
    {
        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0.5', 'max:5', 'multiple_of:0.5'],
        ]);

        return (float) $validated['score'];
    }
}