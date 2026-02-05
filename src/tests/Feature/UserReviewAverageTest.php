<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Review;

class UserReviewAverageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 評価がないユーザーは平均がnullになる()
    {
        $user = User::factory()->create();

        $this->assertNull($user->roundedReviewAverage());
    }

    /** @test */
    public function 評価平均は四捨五入される()
    {
        $ratee  = User::factory()->create(); // 評価される人
        $seller = User::factory()->create(); // Item出品者（Purchaseの整合性用）
        $item   = Item::factory()->for($seller, 'user')->create();

        // rater1
        $rater1 = User::factory()->create();
        $p1 = Purchase::factory()->create([
            'user_id' => $rater1->id,
            'item_id' => $item->id,
        ]);
        Review::create([
            'purchase_id' => $p1->id,
            'rater_id'    => $rater1->id,
            'ratee_id'    => $ratee->id,
            'score'       => 3.0,
        ]);

        // rater2
        $rater2 = User::factory()->create();
        $p2 = Purchase::factory()->create([
            'user_id' => $rater2->id,
            'item_id' => $item->id,
        ]);
        Review::create([
            'purchase_id' => $p2->id,
            'rater_id'    => $rater2->id,
            'ratee_id'    => $ratee->id,
            'score'       => 4.0,
        ]);

        // rater3
        $rater3 = User::factory()->create();
        $p3 = Purchase::factory()->create([
            'user_id' => $rater3->id,
            'item_id' => $item->id,
        ]);
        Review::create([
            'purchase_id' => $p3->id,
            'rater_id'    => $rater3->id,
            'ratee_id'    => $ratee->id,
            'score'       => 4.5,
        ]);

        // (3.0 + 4.0 + 4.5) / 3 = 3.833... → 4
        $this->assertSame(4, $ratee->fresh()->roundedReviewAverage());
    }

    /** @test */
    public function プロフィール画面で評価が表示される()
    {
        $user   = User::factory()->create(); // ログインユーザー（表示対象）
        $seller = User::factory()->create();
        $item   = Item::factory()->for($seller, 'user')->create();

        $rater = User::factory()->create();
        $purchase = Purchase::factory()->create([
            'user_id' => $rater->id,
            'item_id' => $item->id,
        ]);

        Review::create([
            'purchase_id' => $purchase->id,
            'rater_id'    => $rater->id,
            'ratee_id'    => $user->id,
            'score'       => 5.0,
        ]);

        $this->actingAs($user);

        $res = $this->get(route('mypage'));

        // Bladeの表示に合わせて「★」と「5」を見る（"★ 5" の場合もOK）
        $res->assertSee('★');
        $res->assertSee('5');
    }

    /** @test */
    public function 評価がない場合はプロフィールに表示されない()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $res = $this->get(route('mypage'));

        $res->assertDontSee('★');
    }
}