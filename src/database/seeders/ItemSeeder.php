<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\User;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $sellerA = User::where('email', 'seller1@example.com')->firstOrFail();
        $sellerB = User::where('email', 'seller2@example.com')->firstOrFail();

        $items = [
            // C001〜C005（先頭5件）は user_id を sellerA にする
            [
                'name' => '腕時計',
                'price' => 15000,
                'brand' => 'Rolex',
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'image_path' => 'items/Armani+Mens+Clock.jpg',
                'condition' => '良好',
                'user_id' => $sellerA->id,
                'category_ids' => [1, 5],
            ],
            [
                'name' => 'HDD',
                'price' => 5000,
                'brand' => '西芝',
                'description' => '高速で信頼性の高いハードディスク',
                'image_path' => 'items/HDD+Hard+Disk.jpg',
                'condition' => '目立った傷や汚れなし',
                'user_id' => $sellerA->id,
                'category_ids' => [2],
            ],
            [
                'name' => '玉ねぎ3束',
                'price' => 300,
                'brand' => '',
                'description' => '新鮮な玉ねぎ3束のセット',
                'image_path' => 'items/iLoveIMG+d.jpg',
                'condition' => 'やや傷や汚れあり',
                'user_id' => $sellerA->id,
                'category_ids' => [3],
            ],
            [
                'name' => '革靴',
                'price' => 4000,
                'brand' => '',
                'description' => 'クラシックなデザインの革靴',
                'image_path' => 'items/Leather+Shoes+Product+Photo.jpg',
                'condition' => '状態が悪い',
                'user_id' => $sellerA->id,
                'category_ids' => [4],
            ],
            [
                'name' => 'ノートPC',
                'price' => 45000,
                'brand' => '',
                'description' => '高性能なノートパソコン',
                'image_path' => 'items/Living+Room+Laptop.jpg',
                'condition' => '良好',
                'user_id' => $sellerA->id,
                'category_ids' => [5],
            ],

            // C006〜C010（後半5件）は user_id を sellerB にする
            [
                'name' => 'マイク',
                'price' => 8000,
                'brand' => '',
                'description' => '高音質のレコーディング用マイク',
                'image_path' => 'items/Music+Mic+4632231.jpg',
                'condition' => '目立った傷や汚れなし',
                'user_id' => $sellerB->id,
                'category_ids' => [6],
            ],
            [
                'name' => 'ショルダーバッグ',
                'price' => 3500,
                'brand' => '',
                'description' => 'おしゃれなショルダーバッグ',
                'image_path' => 'items/Purse+fashion+pocket.jpg',
                'condition' => 'やや傷や汚れあり',
                'user_id' => $sellerB->id,
                'category_ids' => [7],
            ],
            [
                'name' => 'タンブラー',
                'price' => 500,
                'brand' => '',
                'description' => '使いやすいタンブラー',
                'image_path' => 'items/Tumbler+souvenir.jpg',
                'condition' => '状態が悪い',
                'user_id' => $sellerB->id,
                'category_ids' => [8],
            ],
            [
                'name' => 'コーヒーミル',
                'price' => 4000,
                'brand' => 'Starbacks',
                'description' => '手動のコーヒーミル',
                'image_path' => 'items/Waitress+with+Coffee+Grinder.jpg',
                'condition' => '良好',
                'user_id' => $sellerB->id,
                'category_ids' => [9],
            ],
            [
                'name' => 'メイクセット',
                'price' => 2500,
                'brand' => '',
                'description' => '便利なメイクアップセット',
                'image_path' => 'items/外出メイクアップセット.jpg',
                'condition' => '目立った傷や汚れなし',
                'user_id' => $sellerB->id,
                'category_ids' => [10],
            ],
        ];

        foreach ($items as $data) {
            $item = Item::create([
                'user_id'     => $data['user_id'],
                'name'        => $data['name'],
                'brand'       => $data['brand'],
                'description' => $data['description'],
                'price'       => $data['price'],
                'condition'   => $data['condition'],
                'image_path'  => $data['image_path'],
            ]);

            $item->categories()->attach($data['category_ids']);
        }
    }
}