<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Language extends Model
{
    /** @use HasFactory<\Database\Factories\LanguageFactory> */
    use HasFactory;
    protected $fillable = ['name'];

    /** @return BelongsToMany<Game, $this> */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class);
    }
}
