<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'rater_id',
        'ratee_id',
        'score',
    ];

    protected $casts = [
        'score' => 'float',
    ];


    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }


    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }


    public function ratee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }


    public function isMine(int $userId): bool
    {
        return (int) $this->rater_id === (int) $userId;
    }
}