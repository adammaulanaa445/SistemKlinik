<?php

namespace App\Services;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    public function queueWhatsApp(string $recipientType, ?int $recipientId, string $template, array $payload): NotificationLog
    {
        return NotificationLog::create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'channel' => 'whatsapp',
            'template' => $template,
            'payload' => $payload,
            'status' => 'queued',
        ]);
    }

    public function sendQueued(NotificationLog $log): bool
    {
        $phone = $log->payload['phone'] ?? null;
        $message = $log->payload['message'] ?? '';

        if (! $phone || ! config('services.whatsapp.api_key')) {
            $log->update(['status' => 'failed', 'error_message' => 'Konfigurasi WhatsApp belum lengkap']);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => config('services.whatsapp.api_key'),
            ])->post(config('services.whatsapp.base_url') . '/send', [
                'target' => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                $log->update([
                    'status' => 'sent',
                    'provider_message_id' => $response->json('id') ?? null,
                    'sent_at' => now(),
                ]);
                return true;
            }

            $log->update(['status' => 'failed', 'error_message' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return false;
        }
    }
}