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
        // Upcoming meetings
        Meeting::create([
            'title' => 'Rapat Anggota Tahunan (RAT) 2025',
            'description' => 'Rapat tahunan untuk membahas laporan keuangan dan pemilihan pengurus.',
            'meeting_date' => '2025-12-15 10:00:00',
            'location' => 'Online via Zoom',
            'type' => 'RAT',
            'agenda' => 'Laporan pertanggungjawaban pengurus, rencana kerja 2026, pemilihan pengurus baru.',
            'status' => 'upcoming',
        ]);

        Meeting::create([
            'title' => 'Rapat Evaluasi Kinerja Triwulan 3',
            'description' => 'Evaluasi kinerja triwulan ketiga tahun 2025.',
            'meeting_date' => '2025-11-20 14:00:00',
            'location' => 'Kantor Koperasi KASSA',
            'type' => 'Evaluasi',
            'agenda' => 'Evaluasi kinerja, pembahasan masalah, dan rencana tindak lanjut.',
            'status' => 'upcoming',
        ]);

        // Past meetings
        Meeting::create([
            'title' => 'Rapat Anggota Luar Biasa',
            'description' => 'Rapat luar biasa untuk membahas perubahan anggaran dasar.',
            'meeting_date' => '2025-06-10 09:00:00',
            'location' => 'Online via Google Meet',
            'type' => 'RALB',
            'agenda' => 'Pembahasan perubahan anggaran dasar terkait perluasan unit usaha.',
            'summary' => 'Menyetujui perubahan anggaran dasar terkait perluasan unit usaha simpan pinjam. Dihadiri oleh 45 dari 50 anggota (90% kehadiran).',
            'status' => 'completed',
        ]);

        Meeting::create([
            'title' => 'Rapat Persiapan RAT 2025',
            'description' => 'Rapat persiapan untuk RAT 2025.',
            'meeting_date' => '2025-04-05 13:00:00',
            'location' => 'Kantor Koperasi KASSA',
            'type' => 'Persiapan',
            'agenda' => 'Pembentukan panitia RAT dan penyusunan draf materi.',
            'summary' => 'Pembentukan panitia RAT dan penyusunan draf materi rapat. Terbentuk 3 tim kerja untuk persiapan acara.',
            'status' => 'completed',
        ]);

        $this->command->info('Meetings seeded successfully!');
    }
}
