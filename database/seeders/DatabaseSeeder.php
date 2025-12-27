<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('roles')->truncate();
        DB::table('users')->truncate();
        DB::table('members')->truncate();
        DB::table('savings_accounts')->truncate();
        DB::table('transactions')->truncate();
        DB::table('meetings')->truncate();
        DB::table('shu_distributions')->truncate();
        DB::table('shu_member_allocations')->truncate();

        $this->call([
            RoleSeeder::class,
            DemoUserSeeder::class,
            MemberSeeder::class,
            MasterAkunSeeder::class,
            SavingsAccountSeeder::class,
            TransactionSeeder::class,
            MeetingSeeder::class,
            ShuDistributionSeeder::class,
            ShuMemberAllocationSeeder::class,
        ]);

        Schema::enableForeignKeyConstraints();
    }
}
