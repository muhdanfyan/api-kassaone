<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\SavingsAccount;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['member', 'savingsAccount']);

        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }
        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        return response()->json($query->get());
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['member', 'savingsAccount']);
        return response()->json($transaction);
    }

    public function store(Request $request)
    {
        $request->validate([
            'savings_account_id' => 'required|exists:savings_accounts,id',
            'member_id' => 'required|exists:members,id',
            'transaction_type' => 'required|in:deposit,withdrawal,shu_distribution,fee',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'transaction_date' => 'required|date',
        ]);

        $savingsAccount = SavingsAccount::find($request->savings_account_id);

        if ($request->transaction_type === 'withdrawal' || $request->transaction_type === 'fee') {
            if ($savingsAccount->balance < $request->amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Insufficient balance in savings account.'],
                ]);
            }
            $savingsAccount->balance -= $request->amount;
        } else {
            $savingsAccount->balance += $request->amount;
        }

        $savingsAccount->save();

        $transaction = Transaction::create($request->all());
        $transaction->load(['member', 'savingsAccount']);

        return response()->json($transaction, 201);
    }
}
