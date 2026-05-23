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
        Schema::create('usr_student_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(table: 'usr_users')->cascadeOnDelete();
            $table->string('id_number', 20)->unique();
            $table->string('level', 15);
            $table->string('section', 100);
            $table->string('guardian_name');
            $table->string('guardian_contact_number');
            $table->timestamps();
            $table->softDeletes();
            $table->string('active_id_number', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usr_student_details');
    }
};
