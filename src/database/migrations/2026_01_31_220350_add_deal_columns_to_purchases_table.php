<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDealColumnsToPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchases', function (Blueprint $table) {
            // 取引ステータス（trading / completed）
            $table->string('status')
                ->default('trading')
                ->after('payment_method');

            // 最新メッセージ時刻（並び替え用）
            $table->timestamp('last_message_at')
                ->nullable()
                ->after('status');

            // 未読管理
            $table->timestamp('buyer_last_read_at')
                ->nullable()
                ->after('last_message_at');

            $table->timestamp('seller_last_read_at')
                ->nullable()
                ->after('buyer_last_read_at');

            // 取引完了日時
            // ✅ 実在するカラムの後ろにする
            $table->timestamp('completed_at')
                ->nullable()
                ->after('seller_last_read_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'last_message_at',
                'buyer_last_read_at',
                'seller_last_read_at',
                'completed_at',
            ]);
        });
    }
}