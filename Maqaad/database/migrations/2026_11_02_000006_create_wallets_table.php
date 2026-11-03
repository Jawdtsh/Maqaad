<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->unique()
                ->constrained('students')
                ->restrictOnDelete();
            $table->decimal('balance', 18, 2)
                ->default(0);
        });

        DB::statement("
            ALTER TABLE wallets
            ADD CONSTRAINT chk_wallets_nonnegative_balance
            CHECK (balance >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};