<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('payment_code', 30)->unique(); // contoh: INV-20260924-001
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['tunai', 'transfer', 'qris'])->default('tunai');
            $table->enum('status', ['belum_bayar', 'lunas'])->default('belum_bayar');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};