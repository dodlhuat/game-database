<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use MassPrunable;

    protected $fillable = [
        'user_id',
        'recipient_email',
        'template_key',
        'subject',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Privacy policy §5: email logs are kept for 12 months.
     * Removed daily by the scheduled model:prune.
     *
     * @return Builder<EmailLog>
     */
    public function prunable(): Builder
    {
        return EmailLog::query()->where('sent_at', '<', now()->subMonths(12));
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
