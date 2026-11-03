<?php

use App\Modules\Center\Models\Center;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('course_id');
            $table->foreignId('branch_id');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->decimal('price', 18, 2);
            $table->unsignedInteger('capacity');
            $table->string('status', 32)
                ->default('scheduled');
            $table->timestampTz('cancelled_at')->nullable();
            $table->foreign('center_id')
                ->references('id')
                ->on('centers')
                ->restrictOnDelete();

            $table->foreign(
                ['course_id', 'center_id'],
                'fk_sessions_course_center'
            )
                ->references(['id', 'center_id'])
                ->on('courses')
                ->restrictOnDelete();

            $table->foreign(
                ['branch_id', 'center_id'],
                'fk_sessions_branch_center'
            )
                ->references(['id', 'center_id'])
                ->on('branches')
                ->restrictOnDelete();

            $table->index(
                ['branch_id', 'starts_at'],
                'idx_sessions_branch_starts'
            );
        });

        DB::statement("
            ALTER TABLE sessions
            ADD CONSTRAINT chk_sessions_values
            CHECK (
                price >= 0
                AND capacity > 0
                AND ends_at > starts_at
                AND status IN ('scheduled', 'cancelled')
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};