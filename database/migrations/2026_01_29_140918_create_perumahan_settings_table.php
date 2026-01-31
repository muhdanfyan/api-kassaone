<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('perumahan_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value');
            $table->enum('category', ['general', 'fee', 'security', 'notification', 'waste']);
            $table->enum('data_type', ['string', 'number', 'boolean', 'json'])->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false)->comment('True = cannot be deleted');
            $table->timestamps();
            
            $table->index('category');
            $table->index('setting_key');
        });
        
        // Seed default settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perumahan_settings');
    }
    
    /**
     * Seed default settings
     */
    private function seedDefaultSettings()
    {
        $settings = [
            // General
            ['perumahan_name', 'Tarbiyah Garden', 'general', 'string', 'Nama perumahan'],
            ['perumahan_address', 'Jl. Tarbiyah No. 1, Kota', 'general', 'string', 'Alamat perumahan'],
            ['total_blocks', '8', 'general', 'number', 'Total blok/area'],
            ['total_units', '156', 'general', 'number', 'Total unit rumah'],
            ['contact_phone', '021-12345678', 'general', 'string', 'Nomor telepon kantor'],
            ['contact_email', 'info@tarbiyahgarden.com', 'general', 'string', 'Email kantor'],
            
            // Fee
            ['monthly_fee', '150000', 'fee', 'number', 'Nominal iuran bulanan default'],
            ['due_date', '5', 'fee', 'number', 'Tanggal jatuh tempo (1-31)'],
            ['penalty_enabled', 'true', 'fee', 'boolean', 'Aktifkan denda keterlambatan'],
            ['penalty_per_day', '5000', 'fee', 'number', 'Nominal denda per hari'],
            ['penalty_max_days', '30', 'fee', 'number', 'Maksimal hari denda dihitung'],
            ['grace_period_days', '3', 'fee', 'number', 'Masa tenggang sebelum denda (hari)'],
            
            // Security
            ['shifts_per_day', '3', 'security', 'number', 'Jumlah shift per hari (2 atau 3)'],
            ['patrol_interval', '60', 'security', 'number', 'Interval patroli (menit)'],
            ['auto_patrol_log', 'true', 'security', 'boolean', 'Otomatis catat log patroli'],
            ['require_visitor_photo', 'false', 'security', 'boolean', 'Wajib foto untuk tamu'],
            ['max_visitor_hours', '12', 'security', 'number', 'Maksimal jam kunjungan'],
            
            // Notification
            ['fee_reminder', 'true', 'notification', 'boolean', 'Kirim pengingat iuran'],
            ['fee_reminder_days', '3', 'notification', 'number', 'Hari sebelum jatuh tempo kirim reminder'],
            ['service_notification', 'true', 'notification', 'boolean', 'Notifikasi update layanan'],
            ['waste_notification', 'false', 'notification', 'boolean', 'Notifikasi jadwal sampah'],
            ['waste_notification_hours', '12', 'notification', 'number', 'Jam sebelum jadwal kirim notif'],
            ['notification_method', 'whatsapp', 'notification', 'string', 'Metode notifikasi (email/whatsapp/sms)'],
            
            // Waste
            ['waste_collection_days', 'tuesday,friday', 'waste', 'string', 'Hari pengambilan sampah (comma-separated)'],
            ['waste_collection_time', '07:00', 'waste', 'string', 'Jam pengambilan sampah'],
            ['organic_waste_days', 'tuesday,thursday', 'waste', 'string', 'Hari sampah organik'],
            ['inorganic_waste_days', 'friday', 'waste', 'string', 'Hari sampah anorganik'],
        ];
        
        foreach ($settings as $setting) {
            DB::table('perumahan_settings')->insert([
                'id' => (string) Str::uuid(),
                'setting_key' => $setting[0],
                'setting_value' => $setting[1],
                'category' => $setting[2],
                'data_type' => $setting[3],
                'description' => $setting[4],
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
