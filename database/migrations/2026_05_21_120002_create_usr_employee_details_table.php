<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usr_employee_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(table: 'usr_users')->cascadeOnDelete();
            $table->string('employee_id', 50);
            $table->string('employee_role', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('active_employee_id', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usr_employee_details');
    }
};
