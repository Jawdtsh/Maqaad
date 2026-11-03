<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')
                ->constrained('wallets')
                ->restrictOnDelete();
            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->restrictOnDelete();
            $table->string('type', 32);
            $table->decimal('amount', 18, 2);
            $table->string('operation_key')->unique();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(
                ['wallet_id', 'created_at', 'id'],
                'idx_wallet_transactions_history'
            );

            $table->index(
                ['booking_id', 'type'],
                'idx_wallet_transactions_booking_type'
            );
        });

        DB::statement("
            ALTER TABLE wallet_transactions
            ADD CONSTRAINT chk_wallet_transactions_type_amount
            CHECK (
                (
                    type = 'topup'
                    AND amount > 0
                    AND booking_id IS NULL
                )
                OR
                (
                    type = 'booking_charge'
                    AND amount < 0
                    AND booking_id IS NOT NULL
                )
                OR
                (
                    type IN ('student_refund', 'center_refund')
                    AND amount > 0
                    AND booking_id IS NOT NULL
                )
            )
        ");

        DB::unprepared(<<<'SQL'
            CREATE FUNCTION prevent_wallet_transaction_mutation()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Wallet transactions are immutable';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_wallet_transactions_immutable
            BEFORE UPDATE OR DELETE
            ON wallet_transactions
            FOR EACH ROW
            EXECUTE FUNCTION prevent_wallet_transaction_mutation();
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');

        DB::statement("
            DROP FUNCTION IF EXISTS
            prevent_wallet_transaction_mutation()
        ");
    }
};