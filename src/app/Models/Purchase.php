<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\DealMessage;
use Illuminate\Support\Carbon;

class Purchase extends Model
{
    use HasFactory;

    // 取引ステータス
    public const STATUS_TRADING           = 'trading';
    public const STATUS_BUYER_REVIEWED    = 'buyer_reviewed';
    public const STATUS_COMPLETED         = 'completed';

    protected $fillable = [
        // 既存
        'user_id',
        'item_id',
        'user_address_id',
        'postcode',
        'address',
        'building',
        'payment_method',

        // 取引機能
        'status',
        'last_message_at',
        'buyer_last_read_at',
        'seller_last_read_at',
        'completed_at',
    ];

    protected $casts = [
        'last_message_at'     => 'datetime',
        'buyer_last_read_at'  => 'datetime',
        'seller_last_read_at' => 'datetime',
        'completed_at'        => 'datetime',
    ];



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }


    public function userAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'user_address_id');
    }


    public function messages(): HasMany
    {
        return $this->hasMany(DealMessage::class);
    }


    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }


    public function seller(): ?User
    {
        return $this->item?->user;
    }


    public function sellerId(): ?int
    {

        if ($this->relationLoaded('item') && $this->item) {
            return (int) $this->item->user_id;
        }


        return Item::whereKey($this->item_id)->value('user_id');
    }

    public function isTrading(): bool
    {
        return $this->status === self::STATUS_TRADING && $this->completed_at === null;
    }


    public function isCompleted(): bool
    {
        if ($this->completed_at !== null) {
            return true;
        }


        return $this->hasBuyerRated() && $this->hasSellerRated();
    }


    public function isBuyer(int $userId): bool
    {
        return (int) $this->user_id === (int) $userId;
    }


    public function isSeller(int $userId): bool
    {
        $sellerId = $this->sellerId();
        return $sellerId !== null && (int) $sellerId === (int) $userId;
    }



    public function hasBuyerRated(): bool
    {
        return $this->reviews()->where('rater_id', $this->user_id)->exists();
    }

    public function hasSellerRated(): bool
    {
        $sellerId = $this->sellerId();
        if (!$sellerId) return false;

        return $this->reviews()->where('rater_id', (int) $sellerId)->exists();
    }

    

    public function canSellerRate(): bool
    {
        return $this->hasBuyerRated() && !$this->hasSellerRated() && $this->completed_at === null;
    }


    public function canBuyerRate(): bool
    {
        return !$this->hasBuyerRated() && $this->completed_at === null;
    }


    public function syncCompletionStatus(): void
    {
        if ($this->completed_at !== null) return;

        if ($this->hasBuyerRated() && $this->hasSellerRated()) {
            $this->status = self::STATUS_COMPLETED;
            $this->completed_at = now();
            $this->save();
            return;
        }


        if ($this->hasBuyerRated()) {
            $this->status = self::STATUS_BUYER_REVIEWED;
            $this->save();
            return;
        }

        $this->status = self::STATUS_TRADING;
        $this->save();
    }

    public function unreadCountFor(int $userId): int
    {
        $readAt = $this->isBuyer($userId) ? $this->buyer_last_read_at : $this->seller_last_read_at;

        $q = $this->messages()
            ->where('sender_id', '!=', $userId);

        if ($readAt) {
            $q->where('created_at', '>', $readAt);
        }

        return $q->count();
    }


    public function markAsReadBy(int $userId): void
    {

        DealMessage::where('purchase_id', $this->id)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($this->isBuyer($userId)) {
            $this->buyer_last_read_at = now();
        } elseif ($this->isSeller($userId)) {
            $this->seller_last_read_at = now();
        }

        $this->save();
    }
}