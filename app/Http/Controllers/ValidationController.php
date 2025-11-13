<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ValidationController extends Controller
{
    /**
     * Check if a field value is unique in the members table
     * Used for real-time validation during registration
     */
    public function checkUnique(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'field' => 'required|in:username,email,nik,phone_number',
            'value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $field = $request->field;
        $value = $request->value;

        // Check if value exists in database
        $exists = Member::where($field, $value)->exists();

        // Prepare response messages
        $messages = [
            'username' => $exists 
                ? 'Username sudah digunakan. Silahkan pilih username lain.' 
                : 'Username tersedia',
            'email' => $exists 
                ? 'Email sudah terdaftar. Gunakan email lain atau klik "Lupa Password?"' 
                : 'Email tersedia',
            'nik' => $exists 
                ? 'NIK sudah terdaftar dalam sistem. Hubungi admin jika ada kesalahan.' 
                : 'NIK valid dan tersedia',
            'phone_number' => $exists 
                ? 'Nomor HP sudah terdaftar. Gunakan nomor lain atau hubungi admin.' 
                : 'Nomor HP tersedia',
        ];

        return response()->json([
            'available' => !$exists,
            'message' => $messages[$field],
        ], 200);
    }
}
