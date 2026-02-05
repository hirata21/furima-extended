<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'sender_id',
        'receiver_id',
        'body',
        'image_path',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }


    public function hasImage(): bool
    {
        return !empty($this->image_path);
    }

    public function isMine(int $userId): bool
    {
        return (int) $this->sender_id === (int) $userId;
    }

    public function imageUrl(): ?string
    {
        if (!$this->hasImage()) {
            return null;
        }
        return 'storage/' . $this->image_path;
    }
}