<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')
                ->constrained('centers')
                ->restrictOnDelete();
            $table->string('title');
            $table->unsignedInteger('duration_minutes');
            $table->unique(
                ['id', 'center_id'],
                'uq_courses_id_center'
            );
        });

        DB::statement("
            ALTER TABLE courses
            ADD CONSTRAINT chk_courses_duration
            CHECK (duration_minutes > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};