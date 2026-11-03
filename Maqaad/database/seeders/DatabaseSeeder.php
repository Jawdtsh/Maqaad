<?php

namespace Database\Seeders;

use Database\Seeders\StudentWalletSeeder;
use Database\Seeders\TrainingCatalogSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                TrainingCatalogSeeder::class,
                StudentWalletSeeder::class,
            ]);
        });
    }
}
