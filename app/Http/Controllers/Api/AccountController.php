<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class AccountController extends Controller
{
    /**
     * Display a listing of accounts
     */
    public function index()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $accounts = Account::with(['creator:id,full_name', 'children.children'])
                ->whereNull('parent_id')
                ->orderBy('code', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $accounts
            ]);
        } catch (\Exception $e) {
            Log::error('Get accounts error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve accounts'
            ], 500);
        }
    }

    /**
     * Store a newly created account
     */
    public function store(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $validated = $request->validate([
                'name' => 'required|string|min:3|max:255',
                'description' => 'nullable|string|max:1000',
                'parent_id' => 'nullable|exists:accounts,id',
                'type' => 'nullable|string|max:50',
                'group' => 'nullable|string|max:100',
            ]);

            // Generate account code (ACC-XXXX)
            $lastAccount = Account::orderBy('code', 'desc')->first();
            $lastNumber = $lastAccount ? intval(substr($lastAccount->code, 4)) : 0;
            $code = 'ACC-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

            // Ensure uniqueness
            while (Account::where('code', $code)->exists()) {
                $lastNumber++;
                $code = 'ACC-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            }

            $account = Account::create([
                'code' => $code,
                'name' => $validated['name'],
                'parent_id' => $validated['parent_id'] ?? null,
                'type' => $validated['type'] ?? null,
                'group' => $validated['group'] ?? null,
                'description' => $validated['description'] ?? null,
                'created_by' => $user->id,
            ]);

            $account->load('creator:id,full_name');

            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil dibuat',
                'data' => $account
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Create account error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create account'
            ], 500);
        }
    }

    /**
     * Update the specified account
     */
    public function update(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $account = Account::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|min:3|max:255',
                'description' => 'nullable|string|max:1000',
                'parent_id' => 'nullable|exists:accounts,id',
                'type' => 'nullable|string|max:50',
                'group' => 'nullable|string|max:100',
            ]);

            // Only update name, description, and hierarchy, NOT the code
            $account->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'parent_id' => $validated['parent_id'] ?? $account->parent_id,
                'type' => $validated['type'] ?? $account->type,
                'group' => $validated['group'] ?? $account->group,
            ]);

            $account->load('creator:id,full_name');

            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil diperbarui',
                'data' => $account
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Update account error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update account'
            ], 500);
        }
    }

    /**
     * Remove the specified account
     */
    public function destroy($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $account = Account::findOrFail($id);

            // Check if account is used in expenses
            if ($account->expenses()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun tidak dapat dihapus karena masih digunakan dalam transaksi pengeluaran'
                ], 400);
            }

            $account->delete();

            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil dihapus'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Delete account error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account'
            ], 500);
        }
    }
}
