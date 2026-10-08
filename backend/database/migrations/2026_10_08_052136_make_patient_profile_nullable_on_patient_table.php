<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->change();
            $table->enum('gender', ['L', 'P'])->nullable()->change();
            $table->date('birth_date')->nullable()->change();
            $table->text('address')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('nik', 16)->nullable(false)->change();
            $table->enum('gender', ['L', 'P'])->nullable(false)->change();
            $table->date('birth_date')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
        });
    }
};