<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasTable('usr_users')) {
            Schema::rename('users', 'usr_users');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('usr_users') && ! Schema::hasTable('users')) {
            Schema::rename('usr_users', 'users');
        }
    }
};
