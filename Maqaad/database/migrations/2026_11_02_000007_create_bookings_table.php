<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->restrictOnDelete();
            $table->foreignId('session_id')
                ->constrained('sessions')
                ->restrictOnDelete();
            $table->string('status', 32);
            $table->decimal('charged_amount', 18, 2)
                ->nullable();
            $table->decimal('commission_rate_snapshot', 5, 2)
                ->nullable();
            $table->decimal('commission_amount', 18, 2)
                ->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(
                ['student_id', 'status', 'session_id'],
                'idx_bookings_student_status_session'
            );
        });

        DB::statement("
            ALTER TABLE bookings
            ADD CONSTRAINT chk_bookings_status
            CHECK (
                status IN (
                    'confirmed',
                    'waitlisted',
                    'cancelled_by_student',
                    'cancelled_by_center'
                )
            )
        ");

        DB::statement("
            ALTER TABLE bookings
            ADD CONSTRAINT chk_bookings_financial_snapshots
            CHECK (
                (
                    charged_amount IS NULL
                    AND commission_rate_snapshot IS NULL
                    AND commission_amount IS NULL
                    AND confirmed_at IS NULL
                )
                OR
                (
                    charged_amount IS NOT NULL
                    AND charged_amount >= 0
                    AND commission_rate_snapshot IS NOT NULL
                    AND commission_rate_snapshot BETWEEN 0 AND 100
                    AND commission_amount IS NOT NULL
                    AND commission_amount >= 0
                    AND commission_amount <= charged_amount
                    AND confirmed_at IS NOT NULL
                )
            )
        ");

        DB::statement("
            ALTER TABLE bookings
            ADD CONSTRAINT chk_bookings_waitlisted_without_charge
            CHECK (
                status <> 'waitlisted'
                OR charged_amount IS NULL
            )
        ");

        DB::statement("
            CREATE UNIQUE INDEX uq_bookings_active_student_session
            ON bookings (student_id, session_id)
            WHERE status IN ('confirmed', 'waitlisted')
        ");

        DB::statement("
            CREATE INDEX idx_bookings_waitlist_fifo
            ON bookings (session_id, created_at, id)
            WHERE status = 'waitlisted'
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};