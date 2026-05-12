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
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(table: "users", column: "id")->cascadeOnDelete();
            $table->foreignId('turnstile_id')->constrained(table: "turnstiles", column: "id")->cascadeOnDelete();
            $table->enum('action',['IN', 'OUT']);
            $table->string('scanned_at');
            $table->enum('sms_status', ["PENDING", "SENT", "FAILED"])->default("PENDING");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
