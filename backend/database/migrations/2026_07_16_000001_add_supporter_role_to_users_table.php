<?php

use App\Support\EnumColumn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        EnumColumn::allow('users', 'role', ['USER', 'SUPPORTER', 'MEMBER', 'ADMIN'], 'USER');
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'USER' WHERE role = 'SUPPORTER'");

        EnumColumn::allow('users', 'role', ['USER', 'MEMBER', 'ADMIN'], 'USER');
    }
};
