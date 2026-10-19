<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->enum('insurance_type', ['bpjs', 'umum'])->default('umum')->after('address');
            $table->string('bpjs_number')->nullable()->unique()->after('insurance_type');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['insurance_type', 'bpjs_number']);
        });
    }
};