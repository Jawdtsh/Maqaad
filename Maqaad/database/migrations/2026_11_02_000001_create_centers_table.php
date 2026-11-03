<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('commission_rate', 5, 2);
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("
            ALTER TABLE centers
            ADD CONSTRAINT chk_centers_commission_rate
            CHECK (commission_rate BETWEEN 0 AND 100)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};