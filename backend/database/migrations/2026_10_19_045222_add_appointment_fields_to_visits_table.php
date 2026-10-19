<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->date('appointment_date')->nullable()->after('visit_date');
            $table->enum('visit_type', ['walk_in', 'appointment'])->default('walk_in')->after('appointment_date');
            $table->enum('appointment_status', ['booked', 'checked_in', 'in_progress', 'completed', 'cancelled', 'no_show'])->nullable()->after('visit_type');
            $table->time('estimated_start_time')->nullable()->after('appointment_status');
            $table->string('cancellation_reason')->nullable()->after('estimated_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['appointment_date', 'visit_type', 'appointment_status', 'estimated_start_time', 'cancellation_reason']);
        });
    }
};