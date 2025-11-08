<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;

$member = Member::where('username', 'binatang')->first();

if ($member) {
    echo "Testing pending user login:\n";
    echo "Username: {$member->username}\n";
    echo "Verification Status: {$member->verification_status}\n";
    echo "Can Login: " . ($member->canLogin() ? 'YES' : 'NO') . "\n";
    echo "Payment Amount: {$member->payment_amount}\n";
} else {
    echo "Member 'binatang' not found\n";
}
