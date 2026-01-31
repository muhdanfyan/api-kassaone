<?php

namespace App\Modules\Perumahan\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class UpdateResidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $residentId = $this->route('id');
        
        return [
            'house_number' => 'sometimes|string|max:20|unique:estate_residents,house_number,' . $residentId,
            'owner_name' => 'sometimes|string|max:100',
            'owner_phone' => 'sometimes|string|max:20',
            'owner_email' => 'nullable|email|max:100',
            'nik' => 'nullable|string|max:20',
            'house_status' => 'sometimes|in:owner_occupied,rented,vacant',
            'house_type' => 'sometimes|in:36,45,54,60,70,custom',
            'occupant_count' => 'sometimes|integer|min:1',
            'has_vehicle' => 'nullable|boolean',
            'vehicle_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'joined_date' => 'nullable|date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'house_number.unique' => 'Nomor rumah/blok sudah terdaftar',
            'owner_name.required' => 'Nama pemilik harus diisi',
            'owner_phone.required' => 'Nomor telepon harus diisi',
            'house_status.in' => 'Status hunian tidak valid',
            'house_type.in' => 'Tipe rumah tidak valid. Pilihan: type_36, type_45, type_54, type_60, type_70, custom',
            'occupant_count.integer' => 'Jumlah penghuni harus berupa angka',
            'occupant_count.min' => 'Jumlah penghuni minimal 1',
            'status.in' => 'Status tidak valid',
        ];
    }

    /**
     * Transform the data before validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $data = [];
        
        // Handle occupant_count mapping to total_occupants
        if ($this->has('occupant_count')) {
            $data['total_occupants'] = $this->input('occupant_count');
        }
        
        // Handle house_type mapping (remove "type_" prefix if exists)
        if ($this->has('house_type')) {
            $houseType = $this->input('house_type');
            // Map type_36 -> 36, type_45 -> 45, etc.
            $data['house_type'] = str_replace('type_', '', $houseType);
        }
        
        $this->merge($data);
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        Log::error('Resident Update Validation Failed', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->all(),
        ]);

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
