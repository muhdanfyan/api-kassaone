<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('account');
        
        // Filter by account
        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }
        
        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('expense_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('expense_date', '<=', $request->end_date);
        }
        
        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%");
            });
        }
        
        $expenses = $query->orderBy('expense_date', 'desc')->get();
        
        // Calculate summary
        $summary = [
            'total_amount' => $expenses->sum('amount'),
            'total_count' => $expenses->count(),
            'by_account' => Expense::select('account_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('account_id')
                ->with('account:id,name')
                ->get()
                ->map(function ($item) {
                    return [
                        'account_id' => $item->account_id,
                        'account_name' => $item->account?->name ?? 'Unknown',
                        'total' => (float) $item->total,
                        'count' => $item->count,
                    ];
                }),
        ];
        
        // Map expenses with account info
        $data = $expenses->map(function ($expense) {
            return [
                'id' => $expense->id,
                'account_id' => $expense->account_id,
                'account_name' => $expense->account?->name,
                'account_code' => $expense->account?->code,
                'description' => $expense->description,
                'unit_price' => (float) $expense->unit_price,
                'unit' => $expense->unit,
                'quantity' => $expense->quantity,
                'amount' => (float) $expense->amount,
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'receipt_number' => $expense->receipt_number,
                'notes' => $expense->notes,
                'created_by' => $expense->created_by,
                'created_at' => $expense->created_at,
                'updated_at' => $expense->updated_at,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'description' => 'required|string',
            'unit_price' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:20',
            'quantity' => 'required|integer|min:1',
            'expense_date' => 'required|date',
            'receipt_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $expense = Expense::create([
            'account_id' => $request->account_id,
            'description' => $request->description,
            'unit_price' => $request->unit_price,
            'unit' => $request->unit ?? 'pcs',
            'quantity' => $request->quantity,
            'expense_date' => $request->expense_date,
            'receipt_number' => $request->receipt_number,
            'notes' => $request->notes,
            'created_by' => Auth::id(),
        ]);

        $expense->load('account');

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil ditambahkan',
            'data' => $expense,
        ], 201);
    }

    public function show(string $id)
    {
        $expense = Expense::with('account')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $expense,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $expense = Expense::findOrFail($id);
        
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'description' => 'required|string',
            'unit_price' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:20',
            'quantity' => 'required|integer|min:1',
            'expense_date' => 'required|date',
            'receipt_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $expense->update([
            'account_id' => $request->account_id,
            'description' => $request->description,
            'unit_price' => $request->unit_price,
            'unit' => $request->unit ?? 'pcs',
            'quantity' => $request->quantity,
            'expense_date' => $request->expense_date,
            'receipt_number' => $request->receipt_number,
            'notes' => $request->notes,
        ]);

        $expense->load('account');

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil diupdate',
            'data' => $expense,
        ]);
    }

    public function destroy(string $id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil dihapus',
        ]);
    }
}
