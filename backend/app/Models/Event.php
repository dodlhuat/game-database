<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'date',
        'time',
        'is_all_day',
        'description',
        'image_url',
    ];

    protected $casts = [
        'date'       => 'date',
        'is_all_day' => 'boolean',
    ];
}
