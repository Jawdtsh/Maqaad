<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')
                ->constrained('centers')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('city');
            $table->string('timezone', 64);

            $table->unique(
                ['id', 'center_id'],
                'uq_branches_id_center'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};