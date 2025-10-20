<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShuDistribution;
use App\Models\Member;
use App\Models\ShuMemberAllocation;

class ShuMemberAllocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shu2024 = ShuDistribution::where('fiscal_year', 2024)->first();
        $members = Member::all();

        if ($shu2024 && $members->isNotEmpty()) {
            $memberServiceAllocation = $shu2024->total_shu * ($shu2024->member_service_percentage / 100);
            $allocationPerMember = $memberServiceAllocation / $members->count();

            foreach ($members as $member) {
                ShuMemberAllocation::create([
                    'shu_distribution_id' => $shu2024->id,
                    'member_id' => $member->id,
                    'amount_allocated' => $allocationPerMember,
                ]);
            }
        }
    }
}
