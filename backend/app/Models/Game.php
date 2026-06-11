<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property bool|null $is_favorited
 * @property bool|null $already_borrowed
 * @property int|null $available_copies_count
 */
class Game extends Model
{
    /** @use HasFactory<\Database\Factories\GameFactory> */
    use HasFactory;
    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'category_id',
        'min_players',
        'max_players',
        'min_age',
        'duration_min',
        'duration_max',
        'difficulty',
        'year',
        'instagram_url',
        'deposit_tokens',
        'cover_image_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_players' => 'integer',
            'max_players' => 'integer',
            'min_age' => 'integer',
            'duration_min' => 'integer',
            'duration_max' => 'integer',
            'year' => 'integer',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'game_tags');
    }

    /** @return BelongsToMany<Language, $this> */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class);
    }

    /** @return HasMany<Copy, $this> */
    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class);
    }

    /** @return HasMany<GameImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(GameImage::class)->orderBy('sort_order');
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** @return HasMany<Reservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** @return HasMany<Favorite, $this> */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }
}
