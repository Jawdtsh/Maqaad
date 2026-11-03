<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained('students')
                ->restrictOnDelete();
            $table->string('idempotency_key', 255);
            $table->string('request_hash', 64);
            $table->unsignedSmallInteger('response_status')
                ->nullable();
            $table->jsonb('response_body')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(
                ['student_id', 'idempotency_key'],
                'uq_idempotency_student_key'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_requests');
    }
};