<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nik', 16)->unique();
            $table->enum('gender', ['L', 'P']);
            $table->date('birth_date');
            $table->string('phone', 20);
            $table->text('address');
            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
