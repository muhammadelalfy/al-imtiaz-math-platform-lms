<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $administratorId = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');
        if ($administratorId) {
            DB::table('users')->where('id', $administratorId)->update(['is_super_admin' => true]);
        }
    }

    public function down(): void
    {
        $administratorId = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');
        if ($administratorId) {
            DB::table('users')->where('id', $administratorId)->update(['is_super_admin' => false]);
        }
    }
};
