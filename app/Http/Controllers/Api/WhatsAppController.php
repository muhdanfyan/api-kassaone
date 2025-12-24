<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    private string $whatsappServiceUrl;

    public function __construct()
    {
        $this->whatsappServiceUrl = config('services.whatsapp.service_url', 'http://localhost:3001');
    }

    /**
     * Get WhatsApp connection status
     */
    public function status()
    {
        try {
            $response = Http::timeout(5)->get("{$this->whatsappServiceUrl}/status");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'status' => 'disconnected',
                'message' => 'WhatsApp service not responding',
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp status check failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Cannot connect to WhatsApp service',
            ]);
        }
    }

    /**
     * Get QR code for WhatsApp authentication
     */
    public function getQR()
    {
        try {
            $response = Http::timeout(30)->get("{$this->whatsappServiceUrl}/qr");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get QR code',
            ], 500);
        } catch (\Exception $e) {
            Log::error('WhatsApp QR fetch failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Cannot connect to WhatsApp service',
            ], 500);
        }
    }

    /**
     * Initialize/restart WhatsApp client
     */
    public function initialize()
    {
        try {
            $response = Http::timeout(30)->post("{$this->whatsappServiceUrl}/initialize");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize WhatsApp',
            ], 500);
        } catch (\Exception $e) {
            Log::error('WhatsApp initialize failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Cannot connect to WhatsApp service',
            ], 500);
        }
    }

    /**
     * Disconnect WhatsApp session
     */
    public function disconnect()
    {
        try {
            $response = Http::timeout(10)->post("{$this->whatsappServiceUrl}/disconnect");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect',
            ], 500);
        } catch (\Exception $e) {
            Log::error('WhatsApp disconnect failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Cannot connect to WhatsApp service',
            ], 500);
        }
    }

    /**
     * Send a test message
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            $response = Http::timeout(30)->post("{$this->whatsappServiceUrl}/send", [
                'phone' => $request->phone,
                'message' => $request->message,
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
            ], 500);
        } catch (\Exception $e) {
            Log::error('WhatsApp send failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Cannot connect to WhatsApp service',
            ], 500);
        }
    }

    /**
     * Get session info
     */
    public function sessionInfo()
    {
        try {
            $response = Http::timeout(5)->get("{$this->whatsappServiceUrl}/session-info");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get session info',
            ], 500);
        } catch (\Exception $e) {
            Log::error('WhatsApp session info failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Cannot connect to WhatsApp service',
            ], 500);
        }
    }
}
