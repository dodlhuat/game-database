<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon_url',
        'sort_order',
    ];

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }
}
