<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10); // NORMAL | BONUS
            $table->string('source', 20); // LEGACY | PURCHASE | MEMBERSHIP | ADMIN
            $table->unsignedInteger('amount');
            $table->unsignedInteger('remaining');
            $table->unsignedInteger('unit_cents')->default(0);
            $table->timestamp('acquired_at');
            $table->timestamp('refundable_after')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'kind', 'remaining']);
            $table->index(['kind', 'expires_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('bonus_tokens')->default(0)->after('tokens');
        });

        Schema::table('token_transactions', function (Blueprint $table) {
            $table->foreignId('token_lot_id')->nullable()->after('token_purchase_id')
                ->constrained('token_lots')->nullOnDelete();
        });

        $types = "'BORROW','DEPOSIT_BLOCK','DEPOSIT_RELEASE','DEPOSIT_FORFEIT','PURCHASE','ADMIN_ADJUSTMENT','BONUS_GRANT','BONUS_EXPIRE','REFUND'";
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('token_transactions', fn (Blueprint $table) => $table->string('type', 32)->change());
        } else {
            DB::statement("ALTER TABLE token_transactions MODIFY COLUMN type ENUM({$types}) NOT NULL");
        }

        // Bestand: vorhandene Token werden als wertlose Legacy-Charge übernommen,
        // damit daraus keine Erstattungsansprüche entstehen.
        DB::table('users')->where('tokens', '>', 0)->orderBy('id')->each(function ($user) {
            DB::table('token_lots')->insert([
                'user_id' => $user->id,
                'kind' => 'NORMAL',
                'source' => 'LEGACY',
                'amount' => $user->tokens,
                'remaining' => $user->tokens,
                'unit_cents' => 0,
                'acquired_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('token_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('token_lot_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bonus_tokens');
        });

        Schema::dropIfExists('token_lots');

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("DELETE FROM token_transactions WHERE type IN ('BONUS_GRANT','BONUS_EXPIRE','REFUND')");
            DB::statement("ALTER TABLE token_transactions MODIFY COLUMN type ENUM('BORROW','DEPOSIT_BLOCK','DEPOSIT_RELEASE','DEPOSIT_FORFEIT','PURCHASE','ADMIN_ADJUSTMENT') NOT NULL");
        }
    }
};
