<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermsVersion extends Model
{
    protected $fillable = [
        'version',
        'content',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
