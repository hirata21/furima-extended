<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Review;

class DealReviewTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 購入者は取引を評価できる()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $item = Item::factory()->for($seller, 'user')->create();

        $purchase = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($buyer);

        $response = $this->post(route('deals.complete', $purchase), [
            'score' => 4.5,
        ]);

        $response->assertRedirect(route('items.index'));

        $this->assertDatabaseHas('reviews', [
            'purchase_id' => $purchase->id,
            'rater_id'    => $buyer->id,
            'ratee_id'    => $seller->id,
            'score'       => 4.5,
        ]);
    }

    /** @test */
    public function 購入者は二重に評価できない()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $item = Item::factory()->for($seller, 'user')->create();

        $purchase = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
        ]);

        // すでに購入者評価済み
        Review::create([
            'purchase_id' => $purchase->id,
            'rater_id'    => $buyer->id,
            'ratee_id'    => $seller->id,
            'score'       => 5.0,
        ]);

        $this->actingAs($buyer);

        $response = $this->post(route('deals.complete', $purchase), [
            'score' => 3.0,
        ]);

        $response->assertRedirect(route('items.index'));

        // レビューが1件のままであること
        $this->assertEquals(
            1,
            Review::where('purchase_id', $purchase->id)
                ->where('rater_id', $buyer->id)
                ->count()
        );
    }

    /** @test */
    public function 出品者は購入者評価前には評価できない()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $item = Item::factory()->for($seller, 'user')->create();

        $purchase = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($seller);

        $this->post(route('deals.complete_seller', $purchase), [
            'score' => 4.0,
        ])->assertStatus(403);
    }

    /** @test */
    public function 出品者が評価すると取引が完了する()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $item = Item::factory()->for($seller, 'user')->create();

        $purchase = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
        ]);

        // 先に購入者評価
        Review::create([
            'purchase_id' => $purchase->id,
            'rater_id'    => $buyer->id,
            'ratee_id'    => $seller->id,
            'score'       => 5.0,
        ]);

        $this->actingAs($seller);

        $response = $this->post(route('deals.complete_seller', $purchase), [
            'score' => 4.0,
        ]);

        $response->assertRedirect(route('items.index'));

        $purchase->refresh();

        $this->assertNotNull($purchase->completed_at);
    }
}