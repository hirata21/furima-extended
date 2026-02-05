<?php

namespace App\Mail;

use App\Models\Purchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DealCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Purchase $purchase;

    public function __construct(Purchase $purchase)
    {
        $this->purchase = $purchase;
    }

    public function build()
    {
        $this->purchase->loadMissing('item.user', 'user');

        return $this->subject('【COACHTECH】取引完了のお知らせ')
            ->markdown('emails.deals.completed', [
                'purchase' => $this->purchase,
                'item'     => $this->purchase->item,
                'buyer'    => $this->purchase->user,
                'seller'   => $this->purchase->item?->user,
            ]);
    }
}