<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShuDistribution;

class ShuDistributionController extends Controller
{
    public function index()
    {
        return response()->json(ShuDistribution::all());
    }

    public function show(ShuDistribution $shuDistribution)
    {
        return response()->json($shuDistribution);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fiscal_year' => 'required|integer|unique:shu_distributions',
            'total_shu_amount' => 'required|numeric|min:0',
            'distribution_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $shuDistribution = ShuDistribution::create($request->all());

        return response()->json($shuDistribution, 201);
    }

    public function update(Request $request, ShuDistribution $shuDistribution)
    {
        $request->validate([
            'fiscal_year' => 'sometimes|required|integer|unique:shu_distributions,fiscal_year,' . $shuDistribution->id,
            'total_shu_amount' => 'sometimes|required|numeric|min:0',
            'distribution_date' => 'sometimes|required|date',
            'notes' => 'nullable|string',
        ]);

        $shuDistribution->update($request->all());

        return response()->json($shuDistribution);
    }
}
