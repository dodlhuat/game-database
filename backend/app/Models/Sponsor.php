<?php

namespace App\Models;

use App\Casts\StorageUrl;
use Database\Factories\SponsorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sponsor extends Model
{
    /** @use HasFactory<SponsorFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'url',
        'logo_url',
        'logo_on_light',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'logo_url' => StorageUrl::class,
        'logo_on_light' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * @param  Builder<Sponsor>  $query
     * @return Builder<Sponsor>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
