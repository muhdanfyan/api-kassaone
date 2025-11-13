<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send WhatsApp message to member
     * 
     * @param string $phoneNumber
     * @param array $data
     * @return bool
     */
    public static function send(string $phoneNumber, array $data): bool
    {
        try {
            // Clean phone number (remove +, spaces, etc.)
            $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
            
            // Ensure starts with 62 (Indonesia)
            if (substr($phone, 0, 1) === '0') {
                $phone = '62' . substr($phone, 1);
            } elseif (substr($phone, 0, 2) !== '62') {
                $phone = '62' . $phone;
            }
            
            // Build message
            $message = "*[KASSA ONE] Akun Dibuat*\n\n";
            $message .= "Halo *{$data['name']}*,\n\n";
            $message .= "Akun keanggotaan Anda telah dibuat oleh admin.\n\n";
            $message .= "*Username:* {$data['username']}\n";
            $message .= "*Password:* {$data['password']}\n\n";
            $message .= "Silakan login dan lengkapi data pendaftaran Anda:\n";
            $message .= "✅ Upload KTP & Selfie\n";
            $message .= "✅ Data Ahli Waris\n";
            $message .= "✅ Pilihan Simpanan\n";
            $message .= "✅ Bukti Pembayaran\n\n";
            $message .= "{$data['login_url']}\n\n";
            $message .= "_Pesan otomatis dari KASSA ONE_";
            
            // Check if WhatsApp is configured
            $apiUrl = config('services.whatsapp.api_url');
            $token = config('services.whatsapp.token');
            
            if (empty($apiUrl) || empty($token)) {
                Log::warning('WhatsApp not configured, skipping notification');
                return false;
            }
            
            // Send via WhatsApp API (example: Fonnte, WooWA, etc.)
            $response = Http::post($apiUrl, [
                'target' => $phone,
                'message' => $message,
                'token' => $token,
            ]);
            
            if ($response->successful()) {
                Log::info("WhatsApp sent to {$phone}");
                return true;
            }
            
            Log::error("WhatsApp failed to {$phone}: " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error("WhatsApp error: " . $e->getMessage());
            return false;
        }
    }
}
