<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDealMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deal_messages', function (Blueprint $table) {
            $table->id();

            // 取引（Purchase）
            $table->foreignId('purchase_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // 送信者
            $table->foreignId('sender_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // 受信者（★未読判定に必須）
            $table->foreignId('receiver_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // メッセージ本文
            $table->text('body');

            // 画像（任意）
            $table->string('image_path')->nullable();

            // 既読日時（null = 未読）
            $table->timestamp('read_at')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deal_messages');
    }
}