<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\DealMessage;

class DealMessagePostTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 本文未入力の場合はエラーになる()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = Item::factory()->for($seller, 'user')->create();

        $purchase = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($buyer);

        $res = $this->from(route('deals.show', $purchase))
            ->post(route('deals.messages.store', $purchase), [
                'body' => '',
            ]);

        $res->assertRedirect(route('deals.show', $purchase));
        $res->assertSessionHasErrors(['body']);

        $this->assertDatabaseCount('deal_messages', 0);
    }

    /** @test */
    public function 本文が401文字以上の場合はエラーになる()
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = Item::factory()->for($seller, 'user')->create();

        $purchase = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($buyer);

        $res = $this->from(route('deals.show', $purchase))
            ->post(route('deals.messages.store', $purchase), [
                'body' => str_repeat('a', 401),
            ]);

        $res->assertRedirect(route('deals.show', $purchase));
        $res->assertSessionHasErrors(['body']);

        $this->assertDatabaseCount('deal_messages', 0);
    }

    /** @test */
    public function 画像がpng_jpeg以外の場合はエラーになる()
    {
        Storage::fake('public');

        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = Item::factory()->for($seller, 'user')->create();

        $purchase = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($buyer);

        $res = $this->from(route('deals.show', $purchase))
            ->post(route('deals.messages.store', $purchase), [
                'body'  => 'hello',
                'image' => UploadedFile::fake()->create('test.gif', 10, 'image/gif'),
            ]);

        $res->assertRedirect(route('deals.show', $purchase));
        $res->assertSessionHasErrors(['image']);

        $this->assertDatabaseCount('deal_messages', 0);
    }

    /** @test */
    public function 本文とpng画像で投稿できる()
    {
        Storage::fake('public');

        $seller = User::factory()->create();
        $buyer  = User::factory()->create();
        $item   = Item::factory()->for($seller, 'user')->create();

        $purchase = Purchase::factory()->create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($buyer);

        $res = $this->post(route('deals.messages.store', $purchase), [
            'body'  => 'こんにちは',
            'image' => UploadedFile::fake()->create(
                'test.png',
                10,
                'image/png'
            ),
        ]);

        $res->assertRedirect(route('deals.show', $purchase));

        $this->assertDatabaseCount('deal_messages', 1);

        $msg = DealMessage::first();
        $this->assertSame('こんにちは', $msg->body);
        $this->assertNotNull($msg->image_path);

        Storage::disk('public')->assertExists($msg->image_path);
    }
}