<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Extension extends Model
{
    protected $fillable = [
        'loan_id',
        'requested_at',
        'requested_due_date',
        'status',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'requested_due_date' => 'date',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
