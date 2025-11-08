<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Updating admin users to verified status...\n";

// Get admin role IDs
$adminRoleIds = DB::table('roles')
    ->whereIn('name', ['Admin', 'Pengurus', 'Pengawas'])
    ->pluck('id');

echo "Found " . count($adminRoleIds) . " admin roles\n";

// Update members with admin roles to verified
$updated = DB::table('members')
    ->whereIn('role_id', $adminRoleIds)
    ->update(['verification_status' => 'verified']);

echo "Updated {$updated} admin users to verified status\n";

// Show updated users
$adminUsers = DB::table('members')
    ->whereIn('role_id', $adminRoleIds)
    ->select('username', 'verification_status')
    ->get();

echo "\nAdmin users:\n";
foreach ($adminUsers as $user) {
    echo "  - {$user->username}: {$user->verification_status}\n";
}

echo "\nDone!\n";
