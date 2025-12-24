<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::orderBy('code')->get();
        
        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $account = Account::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dibuat',
            'data' => $account,
        ], 201);
    }

    public function show(string $id)
    {
        $account = Account::with('expenses')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $account,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $account = Account::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $account->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil diupdate',
            'data' => $account,
        ]);
    }

    public function destroy(string $id)
    {
        $account = Account::findOrFail($id);
        
        if ($account->expenses()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun yang memiliki pengeluaran',
            ], 400);
        }
        
        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dihapus',
        ]);
    }
}
