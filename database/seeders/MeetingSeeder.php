<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Meeting;

class MeetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Meeting::create([
            'title' => 'Rapat Anggota Tahunan 2024',
            'description' => 'Rapat tahunan untuk membahas laporan keuangan dan pemilihan pengurus.',
            'meeting_date' => '2024-12-30 09:00:00',
            'location' => 'Aula KASSA Jakarta',
            'type' => 'RAT',
        ]);

        Meeting::create([
            'title' => 'Rapat Pengurus Bulanan Januari',
            'description' => 'Rapat rutin pengurus untuk evaluasi kinerja bulanan.',
            'meeting_date' => '2025-01-15 14:00:00',
            'location' => 'Ruang Meeting Kantor',
            'type' => 'Bulanan',
        ]);

        Meeting::create([
            'title' => 'Evaluasi SHU Q4 2024',
            'description' => 'Rapat evaluasi sisa hasil usaha kuartal keempat.',
            'meeting_date' => '2025-01-20 10:00:00',
            'location' => 'Online Via Zoom',
            'type' => 'Evaluasi',
        ]);
    }
}
