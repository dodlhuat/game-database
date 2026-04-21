<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'game_tags');
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class);
    }

    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(GameImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }
}
