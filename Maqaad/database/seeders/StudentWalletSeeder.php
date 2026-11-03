<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentWalletSeeder extends Seeder
{
    private const STUDENT_COUNT = 60;
    private const INITIAL_BALANCE = '1000.00';

    public function run(): void
    {
        for ($number = 1; $number <= self::STUDENT_COUNT; $number++) {
            $studentId = $this->findOrCreateStudent($number);

            $this->ensureFundedWallet($studentId);
        }
    }

    private function findOrCreateStudent(int $number): int
    {
        $phone = sprintf('09%08d', $number);

        $id = DB::table('students')
            ->where('phone', $phone)
            ->value('id');

        return $id !== null
            ? (int) $id
            : (int) DB::table('students')->insertGetId([
                'full_name' => "Student {$number}",
                'phone' => $phone,
            ]);
    }

    private function ensureFundedWallet(int $studentId): void
    {
        DB::table('wallets')->insertOrIgnore([
            'student_id' => $studentId,
            'balance' => '0.00',
        ]);

        $wallet = DB::table('wallets')
            ->where('student_id', $studentId)
            ->lockForUpdate()
            ->first();

        $operationKey = "seed:student:{$studentId}:topup";

        if (DB::table('wallet_transactions')
            ->where('operation_key', $operationKey)
            ->exists()) {
            return;
        }

        DB::table('wallet_transactions')->insert([
            'wallet_id' => $wallet->id,
            'booking_id' => null,
            'type' => 'topup',
            'amount' => self::INITIAL_BALANCE,
            'operation_key' => $operationKey,
        ]);

        DB::table('wallets')
            ->where('id', $wallet->id)
            ->update([
                'balance' => DB::raw(
                    'balance + '.self::INITIAL_BALANCE
                ),
            ]);
    }
}