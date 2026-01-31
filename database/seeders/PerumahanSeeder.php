<?php

namespace Database\Seeders;

use App\Modules\Perumahan\Models\EstateResident;
use App\Modules\Perumahan\Models\EstateFee;
use App\Modules\Perumahan\Models\EstateFeePayment;
use App\Modules\Perumahan\Models\EstateService;
use App\Modules\Perumahan\Models\EstateSecurityLog;
use App\Modules\Perumahan\Models\EstateWasteSchedule;
use App\Modules\Perumahan\Models\EstateWasteCollection;
use App\Modules\Perumahan\Models\EstateSetting;
use Illuminate\Database\Seeder;

class PerumahanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏠 Seeding Perumahan Module Data...');

        // 1. Create Residents
        $this->seedResidents();

        // 2. Create Fee Types
        $this->seedFees();

        // 3. Create Waste Schedules
        $this->seedWasteSchedules();

        // 4. Create Security Logs
        $this->seedSecurityLogs();

        // 5. Create Waste Collections
        $this->seedWasteCollections();

        // 6. Create Settings
        $this->seedSettings();

        $this->command->info('✅ Perumahan Module basic data seeding completed!');
    }

    private function seedResidents()
    {
        $this->command->info('Creating residents...');

        $residents = [
            ['house_number' => 'A-01', 'owner_name' => 'Bapak Ahmad Fauzi', 'owner_phone' => '081234560001', 'owner_email' => 'ahmad.fauzi@email.com', 'house_type' => '45', 'house_status' => 'owner_occupied', 'total_occupants' => 4, 'has_vehicle' => true, 'vehicle_count' => 2],
            ['house_number' => 'A-02', 'owner_name' => 'Ibu Siti Nurhaliza', 'owner_phone' => '081234560002', 'owner_email' => 'siti.nur@email.com', 'house_type' => '45', 'house_status' => 'owner_occupied', 'total_occupants' => 3, 'has_vehicle' => true, 'vehicle_count' => 1],
            ['house_number' => 'A-03', 'owner_name' => 'Bapak Budi Santoso', 'owner_phone' => '081234560003', 'owner_email' => 'budi.s@email.com', 'house_type' => '60', 'house_status' => 'rented', 'total_occupants' => 5, 'has_vehicle' => true, 'vehicle_count' => 2],
            ['house_number' => 'A-04', 'owner_name' => 'Ibu Dewi Lestari', 'owner_phone' => '081234560004', 'owner_email' => 'dewi.l@email.com', 'house_type' => '45', 'house_status' => 'owner_occupied', 'total_occupants' => 2, 'has_vehicle' => false, 'vehicle_count' => 0],
            ['house_number' => 'A-05', 'owner_name' => 'Bapak Eko Prasetyo', 'owner_phone' => '081234560005', 'owner_email' => 'eko.p@email.com', 'house_type' => '70', 'house_status' => 'owner_occupied', 'total_occupants' => 6, 'has_vehicle' => true, 'vehicle_count' => 3],
            ['house_number' => 'B-01', 'owner_name' => 'Bapak Fajar Rahman', 'owner_phone' => '081234560006', 'owner_email' => 'fajar.r@email.com', 'house_type' => '45', 'house_status' => 'owner_occupied', 'total_occupants' => 4, 'has_vehicle' => true, 'vehicle_count' => 1],
            ['house_number' => 'B-02', 'owner_name' => 'Ibu Gita Savitri', 'owner_phone' => '081234560007', 'owner_email' => 'gita.s@email.com', 'house_type' => '36', 'house_status' => 'rented', 'total_occupants' => 3, 'has_vehicle' => false, 'vehicle_count' => 0],
            ['house_number' => 'B-03', 'owner_name' => 'Bapak Hendra Wijaya', 'owner_phone' => '081234560008', 'owner_email' => 'hendra.w@email.com', 'house_type' => '45', 'house_status' => 'owner_occupied', 'total_occupants' => 4, 'has_vehicle' => true, 'vehicle_count' => 2],
        ];

        foreach ($residents as $resident) {
            EstateResident::create(array_merge($resident, [
                'status' => 'active',
                'joined_date' => now()->subMonths(rand(1, 24)),
            ]));
        }

        $this->command->line('  ✓ Created ' . count($residents) . ' residents');
    }

    private function seedFees()
    {
        $this->command->info('Creating fee types...');

        $fees = [
            ['fee_name' => 'Iuran Keamanan', 'fee_type' => 'monthly', 'amount' => 50000, 'applies_to' => 'all', 'description' => 'Biaya keamanan 24 jam', 'is_mandatory' => true],
            ['fee_name' => 'Iuran Kebersihan', 'fee_type' => 'monthly', 'amount' => 30000, 'applies_to' => 'all', 'description' => 'Biaya pengelolaan sampah dan kebersihan', 'is_mandatory' => true],
            ['fee_name' => 'Iuran Pemeliharaan Jalan', 'fee_type' => 'yearly', 'amount' => 200000, 'applies_to' => 'all', 'description' => 'Pemeliharaan jalan dan fasilitas umum', 'is_mandatory' => true],
        ];

        foreach ($fees as $fee) {
            EstateFee::create(array_merge($fee, [
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
            ]));
        }

        $this->command->line('  ✓ Created ' . count($fees) . ' fee types');
    }

    private function seedWasteSchedules()
    {
        $this->command->info('Creating waste schedules...');

        $schedules = [
            ['schedule_name' => 'Senin Pagi - Sampah Organik', 'day_of_week' => 'monday', 'time' => '08:00', 'waste_type' => 'organic', 'coverage_area' => ['A-01', 'A-02', 'A-03', 'A-04', 'A-05']],
            ['schedule_name' => 'Kamis Pagi - Sampah Organik', 'day_of_week' => 'thursday', 'time' => '08:00', 'waste_type' => 'organic', 'coverage_area' => ['B-01', 'B-02', 'B-03']],
        ];

        foreach ($schedules as $schedule) {
            EstateWasteSchedule::create(array_merge($schedule, ['is_active' => true]));
        }

        $this->command->line('  ✓ Created ' . count($schedules) . ' waste schedules');
    }

    private function seedSecurityLogs()
    {
        $this->command->info('Creating security logs...');

        $residents = EstateResident::all();
        $count = 0;

        // Entry logs
        for ($i = 0; $i < 5; $i++) {
            $resident = $residents->random();
            EstateSecurityLog::create([
                'log_type' => 'entry',
                'resident_id' => $resident->id,
                'house_number' => $resident->house_number,
                'visitor_name' => 'Tamu ' . ($i + 1),
                'visitor_phone' => '081234' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'visitor_purpose' => 'Berkunjung',
                'vehicle_plate' => 'B ' . rand(1000, 9999) . ' ABC',
                'log_datetime' => now()->subDays(rand(1, 7))->setHour(rand(8, 17)),
                'guard_name' => 'Satpam ' . ['Andi', 'Budi', 'Candra'][rand(0, 2)],
                'guard_shift' => ['morning', 'afternoon'][rand(0, 1)],
            ]);
            $count++;
        }

        // Patrol logs
        for ($i = 0; $i < 3; $i++) {
            EstateSecurityLog::create([
                'log_type' => 'patrol',
                'patrol_area' => 'Blok ' . ['A', 'B', 'C'][rand(0, 2)],
                'patrol_notes' => 'Patroli rutin, kondisi aman',
                'log_datetime' => now()->subDays(rand(1, 3))->setHour(rand(20, 23)),
                'guard_name' => 'Satpam ' . ['Andi', 'Budi', 'Candra'][rand(0, 2)],
                'guard_shift' => 'night',
            ]);
            $count++;
        }

        // Incident log
        $resident = $residents->random();
        EstateSecurityLog::create([
            'log_type' => 'incident',
            'resident_id' => $resident->id,
            'house_number' => $resident->house_number,
            'incident_type' => 'noise',
            'incident_description' => 'Keluhan suara bising dari tetangga',
            'incident_severity' => 'low',
            'incident_status' => 'resolved',
            'log_datetime' => now()->subDays(2)->setHour(22),
            'guard_name' => 'Satpam Andi',
            'guard_shift' => 'night',
            'notes' => 'Sudah ditegur dan diselesaikan',
        ]);
        $count++;

        $this->command->line('  ✓ Created ' . $count . ' security logs');
    }

    private function seedWasteCollections()
    {
        $this->command->info('Creating waste collections...');

        $schedules = EstateWasteSchedule::all();
        $count = 0;

        foreach ($schedules as $schedule) {
            // Create 3 collection records for each schedule
            for ($i = 0; $i < 3; $i++) {
                $collectionDate = now()->subWeeks($i + 1);
                
                EstateWasteCollection::create([
                    'schedule_id' => $schedule->id,
                    'collection_date' => $collectionDate,
                    'collection_time' => $schedule->time,
                    'collector_name' => 'Petugas ' . ['Agus', 'Beni', 'Catur'][rand(0, 2)],
                    'houses_collected' => json_decode($schedule->coverage_area ?? '[]'),
                    'houses_skipped' => [],
                    'total_weight' => rand(50, 150),
                    'status' => 'completed',
                    'notes' => 'Pengambilan lancar',
                    'recorded_by' => null,
                ]);
                $count++;
            }
        }

        $this->command->line('  ✓ Created ' . $count . ' waste collection records');
    }

    private function seedSettings()
    {
        $this->command->info('Creating settings...');

        $settings = [
            ['setting_key' => 'estate_name', 'setting_value' => 'Tarbiyah Garden', 'setting_type' => 'string', 'category' => 'general', 'description' => 'Nama perumahan'],
            ['setting_key' => 'total_houses', 'setting_value' => '120', 'setting_type' => 'number', 'category' => 'general', 'description' => 'Total rumah di perumahan'],
            ['setting_key' => 'late_payment_penalty_per_day', 'setting_value' => '2000', 'setting_type' => 'number', 'category' => 'fees', 'description' => 'Denda keterlambatan per hari'],
            ['setting_key' => 'visitor_registration_required', 'setting_value' => '1', 'setting_type' => 'boolean', 'category' => 'security', 'description' => 'Registrasi pengunjung wajib'],
        ];

        foreach ($settings as $setting) {
            EstateSetting::create($setting);
        }

        $this->command->line('  ✓ Created ' . count($settings) . ' settings');
    }
}
