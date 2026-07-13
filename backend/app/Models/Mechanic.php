<?php

namespace App\Models;

use Database\Factories\MechanicFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Mechanic extends Model
{
    /** @use HasFactory<MechanicFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /** @return BelongsToMany<Game, $this> */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_mechanics');
    }
}
