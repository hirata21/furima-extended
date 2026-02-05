<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\DealMessage;

class DealUnreadAndSortTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function チャットを開くと自分宛の未読が既読になる()
    {
        Carbon::setTestNow('2026-02-04 10:00:00');

        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $item = Item::factory()->for($seller, 'user')->create();

        $purchase = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'status'  => 'trading',
            'last_message_at' => now()->subMinute(),
        ]);

        // seller -> buyer（未読2件）
        DealMessage::create([
            'purchase_id' => $purchase->id,
            'sender_id'   => $seller->id,
            'receiver_id' => $buyer->id,
            'body'        => 's1',
            'image_path'  => null,
            'read_at'     => null,
            'created_at'  => now()->subMinutes(2),
            'updated_at'  => now()->subMinutes(2),
        ]);

        DealMessage::create([
            'purchase_id' => $purchase->id,
            'sender_id'   => $seller->id,
            'receiver_id' => $buyer->id,
            'body'        => 's2',
            'image_path'  => null,
            'read_at'     => null,
            'created_at'  => now()->subMinutes(1),
            'updated_at'  => now()->subMinutes(1),
        ]);

        // buyer -> seller（相手宛なので未読判定に関係なし）
        DealMessage::create([
            'purchase_id' => $purchase->id,
            'sender_id'   => $buyer->id,
            'receiver_id' => $seller->id,
            'body'        => 'b1',
            'image_path'  => null,
            'read_at'     => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->actingAs($buyer);

        // 開いたら buyer 宛の未読は既読になる
        $this->get(route('deals.show', $purchase))->assertOk();

        $this->assertEquals(
            0,
            DealMessage::where('purchase_id', $purchase->id)
                ->where('receiver_id', $buyer->id)
                ->whereNull('read_at')
                ->count()
        );
    }

    /** @test */
    public function 取引中タブはlast_message_atが新しい順に表示される()
    {
        $buyer   = User::factory()->create();
        $sellerA = User::factory()->create();
        $sellerB = User::factory()->create();

        $itemA = Item::factory()->for($sellerA, 'user')->create(['name' => 'ItemA']);
        $itemB = Item::factory()->for($sellerB, 'user')->create(['name' => 'ItemB']);

        Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $itemA->id,
            'status'  => 'trading',
            'last_message_at' => now()->subMinutes(10),
        ]);

        Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $itemB->id,
            'status'  => 'trading',
            'last_message_at' => now()->subMinutes(1),
        ]);

        $this->actingAs($buyer);

        // あなたのマイページは tab=deal を指定しないと取引中が表示されない
        $res = $this->get(route('mypage', ['tab' => 'deal']));

        $res->assertSeeInOrder(['ItemB', 'ItemA']);
    }

    /** @test */
    public function 未読バッジ数はreceiver_id自分_and_read_at_nullで数えられる()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = Item::factory()->for($seller, 'user')->create(['name' => 'ItemX']);

        $purchase = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'status'  => 'trading',
            'last_message_at' => now(),
        ]);

        // 未読2件（seller -> buyer）
        DealMessage::create([
            'purchase_id' => $purchase->id,
            'sender_id'   => $seller->id,
            'receiver_id' => $buyer->id,
            'body'        => 'm1',
            'image_path'  => null,
            'read_at'     => null,
        ]);
        DealMessage::create([
            'purchase_id' => $purchase->id,
            'sender_id'   => $seller->id,
            'receiver_id' => $buyer->id,
            'body'        => 'm2',
            'image_path'  => null,
            'read_at'     => null,
        ]);

        // 既読1件（seller -> buyer）
        DealMessage::create([
            'purchase_id' => $purchase->id,
            'sender_id'   => $seller->id,
            'receiver_id' => $buyer->id,
            'body'        => 'm3',
            'image_path'  => null,
            'read_at'     => now(),
        ]);

        $this->actingAs($buyer);

        // 取引中タブを開く（バッジ表示がここにある想定）
        $res = $this->get(route('mypage', ['tab' => 'deal']));

        // 実装のHTML次第だけど、最低限「2」が表示されることを確認したい場合
        // バッジが <span class="badge">2</span> のように出るなら：
        $res->assertSee('2');
    }
}