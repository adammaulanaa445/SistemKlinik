<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_type', 30);
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('channel', 30)->default('whatsapp');
            $table->string('template', 60);
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('queued');
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};