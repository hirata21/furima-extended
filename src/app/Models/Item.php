<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'brand',
        'description',
        'price',
        'condition',
        'image_path',
        'is_sold',
    ];

    protected $casts = [
        'price'   => 'integer',
        'is_sold' => 'boolean',
    ];

    /* Relations */

    /** 出品者 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** カテゴリ（多対多） */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_item', 'item_id', 'category_id')
            ->withTimestamps();
    }

    /** この商品を「いいね」したユーザー（likes: user_id/item_id） */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'likes', 'item_id', 'user_id')
            ->withTimestamps();
    }

    /** コメント一覧 */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** 購入レコード（1商品=1購入想定） */
    public function purchase(): HasOne
    {
        return $this->hasOne(Purchase::class);
    }

    /* Scopes / Helpers */

    public function scopeUnsold(Builder $query): Builder
    {
        return $query->where('is_sold', false);
    }

    public function scopeSold(Builder $query): Builder
    {
        return $query->where('is_sold', true);
    }

    public function isSold(): bool
    {
        return (bool) $this->is_sold;
    }
}