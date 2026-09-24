<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polyclinics', function (Blueprint $table) {
            $table->string('queue_code', 5)->unique()->default('')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('polyclinics', function (Blueprint $table) {
            $table->dropColumn('queue_code');
        });
    }
};