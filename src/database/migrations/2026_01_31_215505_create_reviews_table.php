<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // 取引
            $table->foreignId('purchase_id')
                ->constrained()
                ->cascadeOnDelete();

            // 評価した人（rater）
            $table->foreignId('rater_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // 評価された人（ratee）
            $table->foreignId('ratee_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // 評価（1〜5）
            $table->decimal('score', 2, 1);

            $table->timestamps();

            // ✅ 同一取引で同一人物は1回だけ評価できる
            $table->unique(['purchase_id', 'rater_id'], 'reviews_purchase_rater_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};