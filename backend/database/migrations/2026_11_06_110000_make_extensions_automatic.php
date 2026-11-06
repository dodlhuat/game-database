<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extensions now take effect immediately and nobody reviews requests anymore.
 * Open requests are closed so they don't count, the member can simply extend
 * again. A loan can be extended twice at most.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('extensions')->where('status', 'PENDING')->update([
            'status' => 'REJECTED',
            'admin_note' => 'Automatisch geschlossen: Verlängerungen brauchen keine Freigabe mehr.',
        ]);

        DB::table('loan_settings')->update(['max_extensions' => 2]);
    }

    public function down(): void
    {
        // Intentionally empty: closed requests cannot be told apart from real rejections.
    }
};
