<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_image',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'          => 'datetime',
        'first_login_redirected_at'  => 'datetime',
    ];

    public function address(): HasOne
    {
        return $this->hasOne(UserAddress::class)->withDefault([
            'postcode'   => '',
            'prefecture' => '',
            'address'    => '',
        ]);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * likedItems() のエイリアス（互換性維持）
     */
    public function likes(): BelongsToMany
    {
        return $this->likedItems();
    }

    public function likedItems(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'likes')->withTimestamps();
    }

    public function dealMessages(): HasMany
    {
        return $this->hasMany(DealMessage::class, 'sender_id');
    }

    public function sentReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'rater_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'ratee_id');
    }

    public function roundedReviewAverage(): ?int
    {
        $avg = $this->receivedReviews()->avg('score');

        if ($avg === null) {
            return null;
        }

        return (int) round($avg);
    }
}