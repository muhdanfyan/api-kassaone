<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses with optional filters
     */
    public function index(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $query = Expense::with(['account:id,code,name', 'creator:id,full_name']);

            // Apply filters
            if ($request->has('account_id')) {
                $query->where('account_id', $request->account_id);
            }

            if ($request->has('start_date')) {
                $query->where('expense_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->where('expense_date', '<=', $request->end_date);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('description', 'LIKE', "%{$search}%")
                      ->orWhere('notes', 'LIKE', "%{$search}%");
                });
            }

            $expenses = $query->orderBy('expense_date', 'desc')->get();

            // Format data to include account info
            $formattedExpenses = $expenses->map(function($expense) {
                return [
                    'id' => $expense->id,
                    'account_id' => $expense->account_id,
                    'account_name' => $expense->account->name ?? null,
                    'account_code' => $expense->account->code ?? null,
                    'description' => $expense->description,
                    'unit_price' => $expense->unit_price,
                    'unit' => $expense->unit,
                    'quantity' => $expense->quantity,
                    'amount' => $expense->amount,
                    'expense_date' => $expense->expense_date->format('Y-m-d'),
                    'receipt_number' => $expense->receipt_number,
                    'notes' => $expense->notes,
                    'created_by' => $expense->created_by,
                    'created_at' => $expense->created_at->toIso8601String(),
                    'updated_at' => $expense->updated_at->toIso8601String(),
                ];
            });

            // Calculate summary
            $totalAmount = $expenses->sum('amount');
            $totalCount = $expenses->count();
            
            $byAccount = $expenses->groupBy('account_id')->map(function($group) {
                $account = $group->first()->account;
                return [
                    'account_id' => $group->first()->account_id,
                    'account_name' => $account->name ?? null,
                    'total' => $group->sum('amount'),
                    'count' => $group->count()
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formattedExpenses,
                'summary' => [
                    'total_amount' => $totalAmount,
                    'total_count' => $totalCount,
                    'by_account' => $byAccount
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get expenses error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve expenses'
            ], 500);
        }
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $validated = $request->validate([
                'account_id' => 'required|exists:accounts,id',
                'description' => 'required|string|min:5|max:1000',
                'unit_price' => 'required|numeric|min:0|max:999999999999.99',
                'unit' => 'required|string|max:20',
                'quantity' => 'required|numeric|min:0.01|max:999999999',
                'expense_date' => 'required|date|before_or_equal:today',
                'receipt_number' => 'nullable|string|max:50',
                'notes' => 'nullable|string|max:1000',
            ]);

            $expense = Expense::create([
                'account_id' => $validated['account_id'],
                'description' => $validated['description'],
                'unit_price' => $validated['unit_price'],
                'unit' => $validated['unit'],
                'quantity' => $validated['quantity'],
                'expense_date' => $validated['expense_date'],
                'receipt_number' => $validated['receipt_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $expense->load(['account:id,code,name', 'creator:id,full_name']);

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil dicatat',
                'data' => [
                    'id' => $expense->id,
                    'account_id' => $expense->account_id,
                    'account_name' => $expense->account->name ?? null,
                    'account_code' => $expense->account->code ?? null,
                    'description' => $expense->description,
                    'unit_price' => $expense->unit_price,
                    'unit' => $expense->unit,
                    'quantity' => $expense->quantity,
                    'amount' => $expense->amount,
                    'expense_date' => $expense->expense_date->format('Y-m-d'),
                    'receipt_number' => $expense->receipt_number,
                    'notes' => $expense->notes,
                    'created_by' => $expense->created_by,
                    'created_at' => $expense->created_at->toIso8601String(),
                    'updated_at' => $expense->updated_at->toIso8601String(),
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Create expense error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense'
            ], 500);
        }
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $expense = Expense::findOrFail($id);

            // Authorization check - only creator or admin can update
            if ($expense->created_by !== $user->id && $user->role->name !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengubah pengeluaran ini'
                ], 403);
            }

            $validated = $request->validate([
                'account_id' => 'required|exists:accounts,id',
                'description' => 'required|string|min:5|max:1000',
                'unit_price' => 'required|numeric|min:0|max:999999999999.99',
                'unit' => 'required|string|max:20',
                'quantity' => 'required|numeric|min:0.01|max:999999999',
                'expense_date' => 'required|date|before_or_equal:today',
                'receipt_number' => 'nullable|string|max:50',
                'notes' => 'nullable|string|max:1000',
            ]);

            $expense->update($validated);
            $expense->load(['account:id,code,name', 'creator:id,full_name']);

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil diperbarui',
                'data' => [
                    'id' => $expense->id,
                    'account_id' => $expense->account_id,
                    'account_name' => $expense->account->name ?? null,
                    'account_code' => $expense->account->code ?? null,
                    'description' => $expense->description,
                    'unit_price' => $expense->unit_price,
                    'unit' => $expense->unit,
                    'quantity' => $expense->quantity,
                    'amount' => $expense->amount,
                    'expense_date' => $expense->expense_date->format('Y-m-d'),
                    'receipt_number' => $expense->receipt_number,
                    'notes' => $expense->notes,
                    'created_by' => $expense->created_by,
                    'created_at' => $expense->created_at->toIso8601String(),
                    'updated_at' => $expense->updated_at->toIso8601String(),
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pengeluaran tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Update expense error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense'
            ], 500);
        }
    }

    /**
     * Remove the specified expense
     */
    public function destroy($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $expense = Expense::findOrFail($id);

            // Authorization check - only creator or admin can delete
            if ($expense->created_by !== $user->id && $user->role->name !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk menghapus pengeluaran ini'
                ], 403);
            }

            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil dihapus'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pengeluaran tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Delete expense error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense'
            ], 500);
        }
    }
}
