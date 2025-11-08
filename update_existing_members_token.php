<?php

/**
 * Script untuk update member yang sudah ada tapi belum punya payment_upload_token
 * Run dengan: php update_existing_members_token.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Member;

echo "🔧 Updating existing members with payment_upload_token...\n\n";

// Get all members yang statusnya pending dan belum punya token
$members = Member::where('verification_status', 'pending')
    ->whereNull('payment_upload_token')
    ->get();

$count = 0;

foreach ($members as $member) {
    $token = bin2hex(random_bytes(32));
    $member->payment_upload_token = $token;
    $member->save();
    
    echo "✅ Updated: {$member->username} - Token: " . substr($token, 0, 20) . "...\n";
    $count++;
}

echo "\n✅ Total updated: {$count} members\n";
echo "💡 Token sekarang tersimpan di database.\n";
echo "💡 Tapi frontend perlu token ini - solusinya:\n";
echo "   1. User harus daftar ulang (recommended), ATAU\n";
echo "   2. Simpan token manual ke localStorage (untuk testing)\n\n";

// Show token for latest member
$latestMember = Member::where('verification_status', 'pending')
    ->whereNotNull('payment_upload_token')
    ->latest()
    ->first();

if ($latestMember) {
    echo "📋 Token untuk member terakhir ({$latestMember->username}):\n";
    echo "   {$latestMember->payment_upload_token}\n\n";
    echo "🔧 Untuk testing manual, run di browser console:\n";
    echo "   localStorage.setItem('payment_upload_token', '{$latestMember->payment_upload_token}');\n";
}
