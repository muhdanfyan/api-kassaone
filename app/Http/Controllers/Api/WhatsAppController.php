<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    /**
     * WhatsApp Web.js Node server URL
     * This should point to your Node.js WhatsApp service
     */
    private function getNodeServerUrl(): string
    {
        return env('WHATSAPP_NODE_URL', 'http://localhost:3001');
    }

    /**
     * Check if Node.js WhatsApp server is available
     */
    private function isNodeServerAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->getNodeServerUrl() . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('WhatsApp Node server not available: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get WhatsApp connection status
     */
    public function getStatus()
    {
        try {
            // Try to connect to Node.js WhatsApp server
            if ($this->isNodeServerAvailable()) {
                $response = Http::timeout(10)->get($this->getNodeServerUrl() . '/status');
                if ($response->successful()) {
                    return response()->json($response->json());
                }
            }

            // Fallback: Return disconnected status
            return response()->json([
                'success' => true,
                'status' => 'disconnected',
                'hasQR' => false,
                'hasSession' => false,
                'message' => 'WhatsApp service belum dijalankan. Jalankan node server WhatsApp terlebih dahulu.',
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp getStatus error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 'error',
                'hasQR' => false,
                'hasSession' => false,
                'message' => 'Gagal mengambil status WhatsApp: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QR Code for WhatsApp login
     */
    public function getQR()
    {
        try {
            if ($this->isNodeServerAvailable()) {
                $response = Http::timeout(10)->get($this->getNodeServerUrl() . '/qr');
                if ($response->successful()) {
                    return response()->json($response->json());
                }
            }

            return response()->json([
                'success' => false,
                'status' => 'disconnected',
                'qr' => null,
                'message' => 'QR Code tidak tersedia. Jalankan node server WhatsApp terlebih dahulu.',
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp getQR error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'qr' => null,
                'message' => 'Gagal mengambil QR Code',
            ], 500);
        }
    }

    /**
     * Get WhatsApp session info
     */
    public function getSessionInfo()
    {
        try {
            if ($this->isNodeServerAvailable()) {
                $response = Http::timeout(10)->get($this->getNodeServerUrl() . '/session-info');
                if ($response->successful()) {
                    return response()->json($response->json());
                }
            }

            return response()->json([
                'success' => false,
                'session' => null,
                'message' => 'Session info tidak tersedia',
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp getSessionInfo error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'session' => null,
                'message' => 'Gagal mengambil session info',
            ], 500);
        }
    }

    /**
     * Initialize WhatsApp connection
     */
    public function initialize()
    {
        try {
            if ($this->isNodeServerAvailable()) {
                $response = Http::timeout(30)->post($this->getNodeServerUrl() . '/initialize');
                if ($response->successful()) {
                    return response()->json($response->json());
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menginisialisasi WhatsApp. Jalankan node server WhatsApp terlebih dahulu.',
                'status' => 'error',
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp initialize error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menginisialisasi WhatsApp: ' . $e->getMessage(),
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * Disconnect WhatsApp session
     */
    public function disconnect()
    {
        try {
            if ($this->isNodeServerAvailable()) {
                $response = Http::timeout(10)->post($this->getNodeServerUrl() . '/disconnect');
                if ($response->successful()) {
                    return response()->json($response->json());
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat memutuskan koneksi WhatsApp',
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp disconnect error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memutuskan koneksi WhatsApp',
            ], 500);
        }
    }

    /**
     * Send test message
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            if ($this->isNodeServerAvailable()) {
                $response = Http::timeout(30)->post($this->getNodeServerUrl() . '/send', [
                    'phone' => $request->phone,
                    'message' => $request->message,
                ]);
                
                if ($response->successful()) {
                    return response()->json($response->json());
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengirim pesan. WhatsApp belum terhubung.',
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp sendTest error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pesan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get broadcast targets (member groups)
     */
    public function getBroadcastTargets()
    {
        try {
            // Get member counts for broadcast options
            $allMembers = \App\Models\Member::where('status', 'Aktif')->count();
            $pendiriMembers = \App\Models\Member::where('status', 'Aktif')->where('member_type', 'Pendiri')->count();
            $biasaMembers = \App\Models\Member::where('status', 'Aktif')->where('member_type', 'Biasa')->count();
            $calonMembers = \App\Models\Member::where('status', 'Aktif')->where('member_type', 'Calon')->count();
            $kehormatanMembers = \App\Models\Member::where('status', 'Aktif')->where('member_type', 'Kehormatan')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    ['value' => 'all', 'label' => 'Semua Anggota Aktif', 'count' => $allMembers],
                    ['value' => 'pendiri', 'label' => 'Anggota Pendiri', 'count' => $pendiriMembers],
                    ['value' => 'biasa', 'label' => 'Anggota Biasa', 'count' => $biasaMembers],
                    ['value' => 'calon', 'label' => 'Calon Anggota', 'count' => $calonMembers],
                    ['value' => 'kehormatan', 'label' => 'Anggota Kehormatan', 'count' => $kehormatanMembers],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp getBroadcastTargets error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Gagal mengambil target broadcast',
            ], 500);
        }
    }

    /**
     * Send broadcast message to multiple members
     */
    public function sendBroadcast(Request $request)
    {
        $request->validate([
            'target' => 'required|string|in:all,pendiri,biasa,calon,kehormatan',
            'message' => 'required|string',
        ]);

        try {
            if (!$this->isNodeServerAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp belum terhubung',
                ]);
            }

            // Get target members
            $query = \App\Models\Member::where('status', 'Aktif')
                ->whereNotNull('phone_number')
                ->where('phone_number', '!=', '');

            if ($request->target !== 'all') {
                $memberType = ucfirst($request->target);
                $query->where('member_type', $memberType);
            }

            $members = $query->get(['id', 'full_name', 'phone_number']);

            $results = [];
            $successCount = 0;
            $failedCount = 0;

            foreach ($members as $member) {
                try {
                    $response = Http::timeout(30)->post($this->getNodeServerUrl() . '/send', [
                        'phone' => $member->phone_number,
                        'message' => $request->message,
                    ]);

                    if ($response->successful() && $response->json('success')) {
                        $results[] = [
                            'name' => $member->full_name,
                            'phone' => $member->phone_number,
                            'status' => 'success',
                        ];
                        $successCount++;
                    } else {
                        $results[] = [
                            'name' => $member->full_name,
                            'phone' => $member->phone_number,
                            'status' => 'failed',
                            'error' => $response->json('message') ?? 'Unknown error',
                        ];
                        $failedCount++;
                    }

                    // Small delay between messages
                    usleep(500000); // 0.5 seconds
                } catch (\Exception $e) {
                    $results[] = [
                        'name' => $member->full_name,
                        'phone' => $member->phone_number,
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                    $failedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Broadcast selesai: {$successCount} berhasil, {$failedCount} gagal",
                'data' => [
                    'total' => count($members),
                    'success' => $successCount,
                    'failed' => $failedCount,
                    'results' => $results,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp sendBroadcast error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim broadcast: ' . $e->getMessage(),
            ], 500);
        }
    }
}
