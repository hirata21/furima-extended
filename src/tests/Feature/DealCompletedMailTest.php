<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

use App\Mail\DealCompletedMail;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;

class DealCompletedMailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 購入者が評価送信すると出品者にメールが送信される()
    {
        Mail::fake();

        // 出品者・購入者
        $seller = User::factory()->create([
            'email' => 'seller@example.com',
        ]);
        $buyer = User::factory()->create();

        // ✅ Item に「出品者(user)」を確実に紐づける
        // Itemモデルのリレーション名が user() の想定
        $item = Item::factory()->for($seller, 'user')->create();

        // ✅ Purchase（購入者＋商品）
        $purchase = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
        ]);

        // 購入者でログイン
        $this->actingAs($buyer);

        // ✅ complete は score 必須（0.5刻み）
        $response = $this->post(route('deals.complete', $purchase), [
            'score' => 5.0,
        ]);

        $response->assertRedirect(route('items.index'));

        // ✅ 出品者へメールが送られたか
        Mail::assertSent(DealCompletedMail::class, function ($mail) use ($seller, $purchase) {
            return $mail->hasTo($seller->email)
                && (int) $mail->purchase->id === (int) $purchase->id;
        });
    }
}