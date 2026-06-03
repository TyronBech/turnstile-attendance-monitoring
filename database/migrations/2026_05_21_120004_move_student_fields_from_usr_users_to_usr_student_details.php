<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('usr_users')) {
            return;
        }

        $hasStudentColumns = Schema::hasColumn('usr_users', 'student_id')
            && Schema::hasColumn('usr_users', 'guardian_name')
            && Schema::hasColumn('usr_users', 'guardian_contact_number');

        if (! $hasStudentColumns) {
            return;
        }

        DB::table('usr_users')
            ->whereNotNull('student_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                $existingDetail = DB::table('usr_student_details')
                    ->where('user_id', $user->id)
                    ->first();

                DB::table('usr_student_details')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'id_number' => $existingDetail?->id_number ?? $user->student_id,
                        'level' => $existingDetail?->level ?? 'N/A',
                        'section' => $existingDetail?->section ?? 'N/A',
                        'guardian_name' => $existingDetail?->guardian_name ?? (string) $user->guardian_name,
                        'guardian_contact_number' => $existingDetail?->guardian_contact_number ?? (string) $user->guardian_contact_number,
                        'active_id_number' => $existingDetail?->active_id_number ?? $user->student_id,
                        'created_at' => $existingDetail?->created_at ?? $user->created_at ?? now(),
                        'updated_at' => now(),
                    ],
                );
            });

        Schema::table('usr_users', function (Blueprint $table): void {
            $table->dropColumn([
                'student_id',
                'guardian_name',
                'guardian_contact_number',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('usr_users')) {
            return;
        }

        if (! Schema::hasColumn('usr_users', 'student_id')) {
            Schema::table('usr_users', function (Blueprint $table): void {
                $table->string('student_id')->nullable()->after('id');
                $table->string('guardian_name')->nullable()->after('email');
                $table->string('guardian_contact_number')->nullable()->after('guardian_name');
            });
        }

        if (! Schema::hasTable('usr_student_details')) {
            return;
        }

        DB::table('usr_student_details')
            ->orderBy('id')
            ->get()
            ->each(function (object $studentDetail): void {
                DB::table('usr_users')
                    ->where('id', $studentDetail->user_id)
                    ->update([
                        'student_id' => $studentDetail->id_number,
                        'guardian_name' => $studentDetail->guardian_name,
                        'guardian_contact_number' => $studentDetail->guardian_contact_number,
                    ]);
            });
    }
};
