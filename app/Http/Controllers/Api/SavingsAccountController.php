<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SavingsAccount;
use App\Models\Member;
use Illuminate\Validation\ValidationException;

class SavingsAccountController extends Controller
{
    public function index(Member $member)
    {
        // Ensure all 3 account types exist
        $accountTypes = ['pokok', 'wajib', 'sukarela'];
        $accounts = [];
        
        foreach ($accountTypes as $type) {
            $account = $member->savingsAccounts()->where('account_type', $type)->first();
            
            if ($account) {
                $accounts[] = $account;
            } else {
                // Create missing account with 0 balance
                $newAccount = $member->savingsAccounts()->create([
                    'account_type' => $type,
                    'balance' => 0,
                ]);
                $accounts[] = $newAccount;
            }
        }
        
        return response()->json($accounts);
    }

    public function show(SavingsAccount $savingsAccount)
    {
        return response()->json($savingsAccount);
    }

    public function store(Request $request, Member $member)
    {
        $request->validate([
            'account_type' => 'required|in:pokok,wajib,sukarela',
            'balance' => 'required|numeric|min:0',
        ]);

        // Check if member already has an account of this type
        if ($member->savingsAccounts()->where('account_type', $request->account_type)->exists()) {
            throw ValidationException::withMessages([
                'account_type' => ['Member already has a savings account of this type.'],
            ]);
        }

        $savingsAccount = $member->savingsAccounts()->create($request->all());

        return response()->json($savingsAccount, 201);
    }

    public function update(Request $request, SavingsAccount $savingsAccount)
    {
        $request->validate([
            'balance' => 'sometimes|required|numeric|min:0',
            'account_type' => 'sometimes|required|in:pokok,wajib,sukarela',
        ]);

        $savingsAccount->update($request->all());

        return response()->json($savingsAccount);
    }
}
