<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Item;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'item_id' => Item::factory(),

            'user_address_id' => null,

            'postcode' => $this->faker->regexify('[0-9]{3}-[0-9]{4}'),
            'address'  => $this->faker->state . $this->faker->city . $this->faker->streetAddress,
            'building' => $this->faker->optional()->secondaryAddress,

            'payment_method' => $this->faker->randomElement([
                'カード支払い',
                'コンビニ支払い',
            ]),

            'status' => 'trading',
            'last_message_at' => null,
            'buyer_last_read_at' => null,
            'seller_last_read_at' => null,
            'completed_at' => null,

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * 取引完了状態
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}