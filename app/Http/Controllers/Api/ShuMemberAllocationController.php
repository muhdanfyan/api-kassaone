<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShuMemberAllocation;
use App\Models\ShuDistribution;
use Illuminate\Validation\ValidationException;

class ShuMemberAllocationController extends Controller
{
    public function index(ShuDistribution $shuDistribution)
    {
        return response()->json($shuDistribution->shuMemberAllocations()->with('member')->get());
    }

    public function show(ShuMemberAllocation $shuMemberAllocation)
    {
        $shuMemberAllocation->load('member');
        return response()->json($shuMemberAllocation);
    }

    public function store(Request $request, ShuDistribution $shuDistribution)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'amount_allocated' => 'required|numeric|min:0',
            'is_paid_out' => 'required|boolean',
            'payout_transaction_id' => 'nullable|exists:transactions,id',
        ]);

        // Check for unique allocation per SHU distribution and member
        if (ShuMemberAllocation::where('shu_distribution_id', $shuDistribution->id)->where('member_id', $request->member_id)->exists()) {
            throw ValidationException::withMessages([
                'member_id' => ['Member already has an SHU allocation for this distribution.'],
            ]);
        }

        $allocation = $shuDistribution->shuMemberAllocations()->create($request->all());
        $allocation->load('member');

        return response()->json($allocation, 201);
    }

    public function update(Request $request, ShuMemberAllocation $shuMemberAllocation)
    {
        $request->validate([
            'amount_allocated' => 'sometimes|required|numeric|min:0',
            'is_paid_out' => 'sometimes|required|boolean',
            'payout_transaction_id' => 'nullable|exists:transactions,id',
        ]);

        $shuMemberAllocation->update($request->all());
        $shuMemberAllocation->load('member');

        return response()->json($shuMemberAllocation);
    }
}
